# **POS "Donde Mechas" \- Ecosistema SaaS Multi-Tenant**

> *"¡Donde la porción sí se respeta\!"*

## **🚀 Descripción del Proyecto**

Plataforma SaaS de punto de venta para locales de comida rápida y gastrobares.
**Varios negocios usan exactamente la misma terminal**, cada uno con sus
productos, etiquetas, pedidos, caja y usuarios completamente separados.

Nació como el POS de "Donde Mechas" y hoy ese negocio es el primer inquilino de
la plataforma, no un caso especial en el código.

La interfaz está optimizada para pantallas táctiles (móvil, tablet y PC), con
aislamiento estricto por `negocio_id` aplicado mediante un scope global.

### Estado actual

| Módulo | Estado |
|---|---|
| Terminal de venta (POS) | Funcional: búsqueda, carrito, lector de códigos, drawer móvil |
| Productos y Etiquetas | CRUD completo |
| Usuarios y roles | CRUD completo, roles por negocio |
| Mi Negocio | Listar, crear y editar negocios |
| Configuración | Datos operativos y prefijo de pedidos |
| Pedidos y Caja | Consulta; la venta y el cuadre están en construcción |
| **Cobro** | **Pendiente**: el botón COBRAR todavía no registra la venta |

## **🛠️ Stack Tecnológico**

* **Backend:** [Laravel 13](https://laravel.com/docs) (PHP 8.3+)
* **Frontend:** [Livewire 4](https://livewire.laravel.com/) + Bootstrap 5.3 + Dark Touch CSS
* **Base de Datos:** PostgreSQL (SaaS Multi-Tenant Schema)
* **Entorno:** [Laravel Sail](https://laravel.com/docs/sail) (Docker)
* **Control de Versiones:** GitHub

Bootstrap, FontAwesome, Bootstrap Icons y las tipografías se empaquetan con
Vite desde `node_modules`, no desde un CDN: la terminal tiene que abrir aunque
el local se quede sin internet.

## **📋 Prerrequisitos**

* [Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker Engine).
* [Git](https://git-scm.com/).

No hace falta instalar PHP, Composer ni Node: todo corre dentro de los
contenedores.

## **⚙️ Instalación y Configuración (Local)**

**1. Clonar el repositorio**

```bash
git clone https://github.com/santi32lopz-svg/donde-mechas-ion.git
cd donde-mechas-ion
```

**2. Instalar dependencias PHP**

`./vendor/bin/sail` todavía no existe, así que se usa un contenedor desechable:

```bash
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
```

**3. Configurar entorno**

```bash
cp .env.example .env
```

**4. Levantar, migrar y compilar**

```bash
docker compose up -d
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate:fresh --seed
docker compose exec laravel.test php artisan storage:link
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

El sistema estará disponible en http://localhost.

> **Windows:** `./vendor/bin/sail` es un script `sh` y PowerShell no puede
> ejecutarlo; además PowerShell 5.1 no admite `&&`. Usa `docker compose exec`
> como arriba, o trabaja desde Git Bash / WSL si prefieres el atajo `sail`.

Para desarrollo con recarga en caliente, deja corriendo `docker compose exec
laravel.test npm run dev` en lugar de `npm run build`.

### **🔐 Credenciales de Prueba (Seeder)**

| Cuenta | Contraseña | Qué demuestra |
|---|---|---|
| `superadmin@dondemechas.com` | `super123` | Administra la plataforma. **Sin negocio asignado**: debe elegir uno al entrar |
| `admin@dondemechas.com` | `admin123` | Administra "Donde Mechas": productos, etiquetas, usuarios |
| `caja@dondemechas.com` | `caja123` | Un solo rol y un solo negocio: entra directo al POS |

Tras el login se elige el rol. Si la cuenta solo puede ejercer uno, esa pantalla
se salta.

## **🤝 Flujo de Trabajo (Gitflow)**

Para mantener el orden y la calidad del código, seguimos estas reglas:

* **Rama main:** Protegida. Solo para despliegue a producción.  
* **Ramas feature/:** Crea siempre una rama para cada funcionalidad nueva (ej. feature/numpad-modal).  
* **Pull Requests:** Todo código debe pasar por una revisión antes de ser integrado.  
* **Mensajes de Commit:** Siguiendo el estándar *Conventional Commits* (feat:, fix:, chore:, docs:).

## **📚 Documentación de Referencia**

Todo el conocimiento del negocio y la arquitectura se encuentra en la carpeta `/docs`:

* [Arquitectura Plataforma Multi-Negocio.md](docs/Arquitectura%20Plataforma%20Multi-Negocio.md) — **Empieza por aquí.** Cómo se resuelve el negocio activo, por qué el rol vive en dos niveles, cómo funciona la numeración de pedidos y qué no debe tocarse del POS. Explica el porqué de cada decisión, no solo el cómo.
* [Guía de Estilos CSS (Dark Touch POS).md](docs/Gu%C3%ADa%20de%20Estilos%20CSS%20(Dark%20Touch%20POS).md) — Sistema de diseño "Dark Touch", componentes táctiles y estrategia responsive. Los tokens `--pos-*` viven en `resources/css/app.css`.
* [Arquitectura SaaS Multi-Tenant.md](docs/Arquitectura%20SaaS%20Multi-Tenant.md) — Modelo de suscripción y planteamiento original del aislamiento.

### Tres reglas que evitan la mayoría de los errores

1. **No filtres por `negocio_id`.** El scope global de `BelongsToNegocio` ya lo hace.
2. **No uses `LIKE` a mano** en búsquedas de texto: en PostgreSQL distingue mayúsculas. Usa `Producto::buscar()`.
3. **No uses `session()->flash()`** para avisos dentro de un componente Livewire: no se ven hasta recargar la página.

Aún sin escribir: reglas de negocio y gramajes, convenciones de código y hoja
de ruta.

*Desarrollado para el crecimiento de "Donde Mechas" y el ecosistema SaaS POS.*

