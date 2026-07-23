# **POS "Donde Mechas" \- Ecosistema SaaS Multi-Tenant**

> *"¡Donde la porción sí se respeta\!"*

## **🚀 Descripción del Proyecto**

Este es el repositorio oficial para el sistema de punto de venta (POS) y gestión de inventarios de "Donde Mechas". El sistema está diseñado como una solución **modular, escalable, táctil y responsive**, optimizada para la operación de alto flujo en locales de comidas rápidas y gastrobares bajo una arquitectura **SaaS Multi-Tenant**.

El sistema permite la toma de pedidos presenciales en múltiples dispositivos (Móvil, Tablet, PC), gestión de inventario dividido (contable vs. no contable), control de turnos de caja y aislamiento estricto por negocio (negocio\_id).

## **🛠️ Stack Tecnológico**

* **Backend:** [Laravel 11](https://laravel.com/docs) (PHP 8.3+)  
* **Frontend:** [Livewire 3](https://livewire.laravel.com/) \+ Bootstrap 5.3 \+ Dark Touch CSS  
* **Base de Datos:** PostgreSQL (SaaS Multi-Tenant Schema)  
* **Entorno:** [Laravel Sail](https://laravel.com/docs/11.x/sail) (Docker)  
* **Control de Versiones:** GitHub

## **📋 Prerrequisitos**

Antes de comenzar, asegúrate de tener instalado en tu máquina:

* [Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker Engine).  
* [Composer](https://getcomposer.org/) (para gestión de dependencias de PHP).  
* [Git](https://git-scm.com/).

## **⚙️ Instalación y Configuración (Local)**

1. **Clonar el repositorio:**

git clone https://github.com/TuUsuario/donde-mechas.git  
cd donde-mechas

2. **Instalar dependencias:**

composer install

3. **Configurar entorno:** Crea tu archivo .env a partir del ejemplo:

cp .env.example .env

4. **Levantar el entorno (Docker/Sail):**

./vendor/bin/sail up \-d

5. **Ejecutar migraciones y datos iniciales:**

./vendor/bin/sail artisan migrate:fresh \--seed

El sistema estará disponible en http://localhost.

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

Todo el conocimiento del negocio y la arquitectura se encuentra en la carpeta /docs:

* [docs/guia\_estilos\_css.md](https://gemini.google.com/app/docs/guia_estilos_css.md): Sistema de diseño "Dark Touch", componentes táctiles, tokens CSS y estrategia responsive (Mobile-First).  
* contexto\_proyecto\_claude.md: Reglas de negocio, gramajes e inventario.  
* estandares\_desarrollo.md: Convenciones de código y protocolo de revisión.  
* plan\_de\_trabajo\_pos.md: Hoja de ruta y fases del proyecto.

*Desarrollado para el crecimiento de "Donde Mechas" y el ecosistema SaaS POS.*

