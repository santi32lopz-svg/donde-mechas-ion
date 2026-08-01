# Arquitectura de la Plataforma Multi-Negocio

Este documento recoge las decisiones estructurales que convirtieron el POS de un
solo local en una plataforma donde varios negocios usan la misma terminal. Está
escrito para alguien que llega nuevo al proyecto: explica **por qué** las cosas
son como son, no solo cómo funcionan.

---

## 1. El negocio activo vive en la sesión, no en el usuario

### El problema

El aislamiento multi-tenant resolvía el negocio así:

```php
tenant = auth()->user()->negocio_id
```

Un usuario pertenecía a un negocio y no podía ver otro. En cuanto la plataforma
necesitó que un administrador gestionara varios, esa premisa dejó de sostenerse.

### La solución

`App\Support\TenantContext` es la **única** fuente del negocio activo. Se
registra como singleton porque es estado de la petición: si se resolviera una
instancia nueva en cada llamada, lo que fija el middleware se perdería.

```
Petición → EstablecerNegocioActivo (middleware)
             ↓ lee session('negocio_activo_id')
             ↓ lo valida contra los negocios del usuario
           TenantContext::usar($id)
             ↓
           Scope global de BelongsToNegocio → WHERE negocio_id = ?
```

### Reglas del aislamiento

| Situación | Comportamiento |
|---|---|
| Sin sesión (consola, seeders, tests, login) | No filtra: no hay tenant con el que filtrar |
| Con sesión y negocio activo | Filtra por ese negocio |
| Con sesión y sin negocio activo | **No devuelve nada** |

El tercer caso es deliberado: **falla cerrado**. Ante la duda, una pantalla
vacía es preferible a mostrar el catálogo de otro negocio.

Para reportes de plataforma existe `TenantContext::sinAislamiento()`, que exige
saltarse el aislamiento de forma explícita y rastreable con un `grep`.

### Qué implica para quien programa

**No escribas `where('negocio_id', ...)` en un módulo nuevo.** El scope ya lo
hace. Si lo escribes a mano estás duplicando una regla que ya tiene dueño, y el
día que cambie tendrás dos sitios que actualizar.

```php
// Correcto: ya viene filtrado
Producto::query()->where('disponible', true)->get();
```

---

## 2. La relación usuario–negocio es N:M

`users.negocio_id` ataba cada persona a un negocio. Se sustituyó por la tabla
pivote `negocio_usuario`, **y la columna se eliminó**: mantener dos mecanismos
para lo mismo garantiza que acaben divergiendo.

```
negocio_usuario
├── negocio_id      ─┐
├── user_id         ─┴─ único conjunto
├── rol              (administrador | cajero)
└── activo           (suspender sin romper el vínculo ni el histórico)
```

Esto permite que una misma persona sea **administradora de un negocio y cajera
de otro** sin conflicto, que es la razón de existir del pivote.

### La migración

No borró la columna a ciegas. Volcó la relación, contó los vínculos creados
contra los usuarios que tenían negocio, comprobó que ninguno apuntara a un
negocio inexistente, y solo entonces eliminó. Como PostgreSQL ejecuta las
migraciones en transacción, cualquier discrepancia habría dejado la base intacta.

**Ese es el estándar del proyecto para migraciones destructivas**: fotografiar,
migrar, verificar, y solo después destruir. Con `down()` probado ejecutando
rollback real, no solo escrito.

---

## 3. Roles en dos niveles

`App\Enums\RolUsuario` es la única fuente de verdad. Se usa en dos sitios que
conviene no confundir:

| Dónde | Qué significa |
|---|---|
| `users.rol` | Rol de **plataforma**. Solo `superadmin` tiene alcance global |
| `negocio_usuario.rol` | Rol **en ese negocio**: el que decide qué puede hacer |

El superadministrador **no tiene vínculos en el pivote a propósito**: alcanza
todos los negocios por su rol, así que asignarle uno sería arbitrario. Al entrar
debe elegir sobre cuál trabaja, y `RequiereNegocioActivo` lo redirige a
seleccionarlo en lugar de mostrarle un panel vacío.

Añadir un rol nuevo es añadir un caso al enum y una migración que amplíe la
restricción `CHECK` de PostgreSQL, que es como Laravel materializa `enum()`.

### Salvaguardas

Sin ellas es posible dejar un negocio sin nadie que pueda gestionarlo:

- No se puede degradar, suspender ni desvincular al **último administrador activo**.
- Nadie puede suspender ni desvincular su propio acceso.
- Desvincular no borra la cuenta: la persona puede seguir trabajando en otros negocios.

---

## 4. Etiquetas, no categorías

El proyecto usaba "categoría" en la base de datos y "etiqueta" en el panel. Dos
nombres para el mismo concepto obligan a cada desarrollador nuevo a aprender la
equivalencia, así que se unificó en **etiqueta** en todas partes.

La relación con `Producto` es **1:N** a propósito: un producto pertenece a una
sola etiqueta, que es lo que espera la cuadrícula del POS. El nombre sugiere
N:M, pero cambiar la cardinalidad alteraría la semántica del filtro de la
terminal. Si algún día hace falta, el camino es una tabla `etiqueta_producto`,
no reinterpretar la actual.

### La clave foránea es RESTRICT

Era `ON DELETE CASCADE`: borrar una categoría borraba **en silencio** todos sus
productos. Con un CRUD de etiquetas expuesto al usuario eso habría sido
catastrófico. Ahora el borrado exige reasignar antes, y el componente lo explica
en lugar de dejar escapar un error de integridad.

El mismo criterio aplica a productos: `detalle_pedidos` los referencia con
`NO ACTION`, así que un producto **ya vendido no se borra** — se marca como no
disponible. Borrarlo falsearía el histórico de ventas.

---

## 5. Numeración de pedidos

`pedidos.numero_pedido` era un `varchar` con `UNIQUE` global. En cuanto dos
negocios emitieran su pedido «1», el segundo fallaba.

### Diseño actual

```
secuencias_pedido
├── negocio_id (único)   ← lo que hace segura la creación bajo carrera
└── ultimo_numero        ← entero, continuo, nunca se reinicia
```

`App\Support\SecuenciaDePedidos::siguiente()` bloquea la fila del negocio con
`lockForUpdate` mientras incrementa. Calcularlo con `MAX(numero) + 1` es más
simple, pero con dos cajeros cobrando a la vez ambos leen el mismo máximo y uno
choca contra el índice único. Con el bloqueo solo se serializan las peticiones
de número, y **dos negocios distintos no se estorban** porque el bloqueo es por
fila.

La fila se crea con `insertOrIgnore` apoyándose en el índice único: un «si no
existe, inserta» tendría condición de carrera.

**Debe llamarse dentro de la transacción de la venta.** Si la venta se deshace,
el número se libera con ella y no deja huecos.

### El prefijo es presentación

El consecutivo se guarda como entero. `negocios.prefijo_pedido` solo interviene
al mostrarlo: `DM-000123`. Cambiarlo no toca ninguna fila.

⚠️ **Contrapartida conocida:** cambiar el prefijo también cambia cómo se
muestran los pedidos ya emitidos. Un pedido impreso como `DM-000042` pasaría a
verse `XX-000042`. Está avisado en la pantalla de Configuración.

---

## 6. El POS es el mismo para todos los negocios

`App\Livewire\Pos\PosMain` **no se duplicó por negocio**. Consume el negocio
activo y punto. De toda la reestructuración, el componente cambió exactamente
una línea:

```php
$this->negocioId = app(TenantContext::class)->negocioId();
```

Eso es la prueba de que el aislamiento está en el sitio correcto.

### Lo que no debe tocarse sin motivo

El carrito quedó blindado antes de la reestructuración y esas decisiones siguen
vigentes:

- **El carrito no transporta precios.** Guarda solo cantidad y notas; nombre,
  precio y subtotal se resuelven contra la base de datos en cada render. Antes
  el precio viajaba en una propiedad pública de Livewire, es decir, dentro del
  payload que el navegador puede editar.
- `#[Locked]` en `$cart` y `$negocioId`.
- `$subtotal` y `$total` no son propiedades: se calculan en `render()`.
- `wire:ignore.self` en el drawer móvil, porque Livewire descartaba la clase
  `show` que añade Bootstrap y el panel se cerraba solo.

---

## 7. Detalles con historia

Cosas que parecen arbitrarias y no lo son:

**`Producto::buscar()` elige el operador según el driver.** En PostgreSQL `LIKE`
distingue mayúsculas, así que buscar «hamb» no encontraba «Hamburguesa». El
scope usa `ILIKE` en pgsql y `LIKE` en el resto. **No escribas `LIKE` a mano en
una búsqueda de texto.**

**Los avisos van en propiedades del componente, no en `session()->flash()`.** Una
actualización de Livewire es AJAX y no recarga el layout, así que un mensaje
flasheado no se ve hasta la siguiente navegación completa.

**El slug no se regenera al renombrar un negocio.** Alimenta URLs y romper
enlaces compartidos sería un efecto colateral silencioso.

**Los diálogos rápidos viven en el mismo componente que los invoca.** El modal
de creación de etiqueta desde el formulario de producto comparte instancia, así
que abrirlo y cerrarlo no toca lo que el usuario ya escribió.

---

## 8. Mapa de la aplicación

```
LOGIN
  ↓
/seleccionar-rol          ← se salta si solo puede ejercer uno
  ├── Administrador → /admin
  └── Cajero        → /pos   (o /seleccionar-negocio si tiene varios)

/admin
├── /                Dashboard
├── /mi-negocio      Listar, crear y editar negocios
├── /productos       CRUD + alta rápida de etiqueta
├── /etiquetas       CRUD
├── /pedidos         Histórico de ventas
├── /caja            Turnos y cuadre
├── /usuarios        Quién entra y con qué rol
└── /configuracion   Datos operativos y prefijo de pedidos
```

### Archivos clave

| Archivo | Papel |
|---|---|
| `app/Support/TenantContext.php` | Fuente única del negocio activo |
| `app/Models/Concerns/BelongsToNegocio.php` | Scope global de aislamiento |
| `app/Http/Middleware/EstablecerNegocioActivo.php` | Resuelve el tenant por petición |
| `app/Http/Middleware/RequiereNegocioActivo.php` | Protege lo que exige negocio elegido |
| `app/Enums/RolUsuario.php` | Única fuente de roles |
| `app/Support/SecuenciaDePedidos.php` | Consecutivo por negocio |

---

## 9. Cómo añadir un módulo nuevo

1. Crea el componente en `app/Livewire/Admin/<Modulo>/Index.php`.
2. Usa el trait `BelongsToNegocio` en sus modelos. **No filtres por
   `negocio_id`**: ya está hecho.
3. Devuelve `->layout('components.layouts.admin')`.
4. Añade la ruta dentro del grupo con `RequiereNegocioActivo`.
5. Añade la sección al array `$secciones` de
   `resources/views/components/layouts/admin.blade.php`.
6. Usa propiedades para los avisos, no `flash`.
7. Escribe un test de aislamiento: que un negocio no vea lo del otro.

Los estilos reutilizan los tokens `--pos-*` del tema Dark Touch. Antes de
inventar una clase nueva, mira si existe `.admin-panel`, `.admin-tabla`,
`.admin-tarjeta` o `.admin-metrica`.
