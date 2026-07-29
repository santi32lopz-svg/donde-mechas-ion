# **POS "Donde Mechas" \- Ecosistema SaaS Multi-Tenant**

> *"¡Donde la porción sí se respeta\!"*

## **🚀 Descripción del Proyecto**

Este es el repositorio oficial para el sistema de punto de venta (POS) y gestión de inventarios de "Donde Mechas". El sistema está diseñado como una solución **modular, escalable, táctil y responsive**, optimizada para la operación de alto flujo en locales de comidas rápidas y gastrobares bajo una arquitectura **SaaS Multi-Tenant**.

El sistema permite la toma de pedidos presenciales en múltiples dispositivos (Móvil, Tablet, PC), gestión de inventario dividido (contable vs. no contable), control de turnos de caja y aislamiento estricto por negocio (negocio\_id).

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

* **Admin General:** admin@dondemechas.com / admin123  
* **Cajero Turno:** caja@dondemechas.com / caja123

## **🤝 Flujo de Trabajo (Gitflow)**

Para mantener el orden y la calidad del código, seguimos estas reglas:

* **Rama main:** Protegida. Solo para despliegue a producción.  
* **Ramas feature/:** Crea siempre una rama para cada funcionalidad nueva (ej. feature/numpad-modal).  
* **Pull Requests:** Todo código debe pasar por una revisión antes de ser integrado.  
* **Mensajes de Commit:** Siguiendo el estándar *Conventional Commits* (feat:, fix:, chore:, docs:).

## **📚 Documentación de Referencia**

Todo el conocimiento del negocio y la arquitectura se encuentra en la carpeta `/docs`:

* [Guía de Estilos CSS (Dark Touch POS).md](docs/Gu%C3%ADa%20de%20Estilos%20CSS%20(Dark%20Touch%20POS).md): Sistema de diseño "Dark Touch", componentes táctiles, tokens CSS y estrategia responsive (Mobile-First). Los tokens `--pos-*` viven en `resources/css/app.css`.
* [Arquitectura SaaS Multi-Tenant.md](docs/Arquitectura%20SaaS%20Multi-Tenant.md): Aislamiento por `negocio_id` y modelo de suscripción.

Aún sin escribir: reglas de negocio y gramajes, convenciones de código y hoja
de ruta. Se listaban aquí como si existieran, pero nunca se crearon.

*Desarrollado para el crecimiento de "Donde Mechas" y el ecosistema SaaS POS.*

