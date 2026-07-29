---
name: estado-tareas-pos
description: Audita una lista de tareas o requisitos contra el código real de este repositorio y devuelve una checklist anotada (hecho / parcial / pendiente / obsoleto) junto con un cuerpo de issue listo para pegar en GitHub. Úsala siempre que el usuario pegue una lista de tareas, un checklist, un objetivo con requisitos numerados o una captura de un tablero, y también cuando pregunte "qué falta", "qué llevamos", "cómo vamos", "tacha lo que ya está" o pida una descripción para GitHub — aunque no use la palabra "auditoría" ni pida explícitamente un diagnóstico.
---

# Estado de tareas del POS

## Por qué existe

En este proyecto las tareas llegan como listas pegadas desde un tablero o un
documento de especificación, y casi siempre están desfasadas respecto al
código: algunas ya se hicieron en un PR anterior, otras se hicieron a medias,
y unas cuantas describen archivos o rutas que ya no existen.

Responder de memoria produce checklists que suenan bien y son falsas. El valor
de esta skill está en que cada marca de "hecho" se apoya en una línea de código
que alguien puede abrir y ver.

## Cómo auditar

Verifica contra el repositorio, no contra el historial de la conversación. Lo
que se dijo hace veinte mensajes pudo revertirse en un `git checkout`, y una
tarea puede haberse resuelto sin que nadie lo mencionara.

Para cada tarea, busca la evidencia concreta antes de asignarle estado:

- **Código de aplicación**: usa Grep/Read sobre el archivo que implementaría la
  tarea. Si la tarea dice "filtrar por `negocio_id`", encuentra la línea que
  filtra.
- **Rama activa**: comprueba en qué rama estás (`git branch --show-current`) y
  si el trabajo vive en una rama sin mergear. Una tarea completada en una rama
  abierta no es lo mismo que una tarea en `main`; dilo.
- **Ejecución real** cuando sea barato: este proyecto corre en Docker, así que
  `docker compose exec -T laravel.test php artisan test` o una consulta directa
  a Postgres valen más que leer el código. Un test verde es la mejor evidencia
  que puedes ofrecer.
- **Datos**: una consulta puede estar perfecta y aun así no devolver nada
  porque la tabla está vacía. Cuando una tarea dependa de datos, míralos.

## Los cuatro estados

Usar solo "hecho" y "pendiente" esconde justo la información que el usuario
necesita para decidir qué hacer después. Distingue:

- ✅ **Hecho** — implementado y verificado. Cita el archivo y, si existe, el
  test que lo cubre.
- 🟡 **Parcial** — funciona en el caso principal pero incumple parte del
  requisito. Di exactamente qué parte falta, no "está incompleto".
- ⬜ **Pendiente** — sin empezar.
- ⚠️ **Obsoleto** — la tarea describe algo que ya no aplica: un archivo
  renombrado, una ruta que no existe, un requisito que el propio proyecto
  contradice. Estos son los hallazgos más valiosos porque nadie los busca.

Cuando una tarea esté hecha de una forma distinta a la que pedía el enunciado,
márcala hecha y explica la desviación en una línea. El objetivo cumplido pesa
más que el método literal.

## Formato de salida

Entrega dos bloques separados, en este orden.

### Bloque 1 — Checklist anotada

Reproduce las tareas del usuario en su orden y con su redacción, cada una con
su estado y una línea de evidencia. Agrupa por los encabezados que el usuario
haya usado, si los hay. Ejemplo del nivel de detalle esperado:

```
✅ Renderizar productos filtrados por negocio_id
   app/Models/Concerns/BelongsToNegocio.php — scope global; cubierto por
   PosMainTest::test_solo_muestra_productos_del_negocio_en_sesion

🟡 Layout de 2 columnas
   Escritorio y tablet listos (resources/css/app.css). En móvil la guía pide
   la tirilla como offcanvas y hoy solo se apila debajo del catálogo.
```

Cierra el bloque con un recuento honesto: cuántas hechas, parciales y
pendientes sobre el total.

### Bloque 2 — Cuerpo de issue para GitHub

Markdown listo para pegar, dentro de un bloque de código para que el usuario
lo copie de una pieza. Escríbelo para alguien que abre el issue sin haber
seguido la conversación:

- Un párrafo de contexto: qué se construyó y dónde vive.
- Una tabla o lista de lo entregado, con enlaces a los archivos.
- **Lo que falta**, que es el corazón del issue: cada punto con checkbox de
  GitHub (`- [ ]`), qué hay que hacer, en qué archivo, y por qué importa.
- Una sección de discrepancias de especificación si las encontraste.

Ordena lo pendiente por impacto real, no por el orden en que aparecía en la
lista original. Si algo bloquea el siguiente hito, ponlo primero y dilo.

## Honestidad del reporte

Este reporte se usa para decidir en qué se trabaja después, así que un "hecho"
optimista cuesta caro. Algunas reglas que vale la pena respetar:

- Si no verificaste algo, dilo en lugar de suponerlo. "No lo comprobé en
  ejecución" es una respuesta legítima.
- Separa "el código está escrito" de "lo vi funcionando". Son cosas distintas
  y el usuario necesita saber cuál de las dos le estás dando.
- Si un trabajo previo tuyo dejó algo roto o a medias, señálalo con el mismo
  criterio que aplicarías al código de otro.
- No infles la lista de pendientes con mejoras que nadie pidió. Distingue lo
  que falta para cumplir el requisito de lo que tú recomendarías además, y
  ponlo en una sección aparte.
