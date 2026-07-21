# **POS "Donde Mechas" \- Gestión de Comidas Rápidas**

> *"¡Donde la porción sí se respeta\!"*

## **🚀 Descripción del Proyecto**

Este es el repositorio oficial para el sistema de punto de venta (POS) y gestión de inventarios de "Donde Mechas". El sistema está diseñado como una solución **modular, escalable y táctil**, optimizada para la operación de alto flujo en locales de comidas rápidas, con el objetivo de expandirse a otros modelos de negocio (como bares) a futuro.

El sistema permite la toma de pedidos, gestión de inventario dividido (contable vs. visual) y control de caja, todo bajo una arquitectura pensada para ser operada rápidamente en entornos táctiles.

## **🛠️ Stack Tecnológico**

* **Backend:** [Laravel 11](https://laravel.com/docs)  
* **Frontend:** [Livewire](https://livewire.laravel.com/) \+ Bootstrap (UI Táctil)  
* **Base de Datos:** PostgreSQL  
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

3. **Configurar entorno:**  
   Crea tu archivo .env a partir del ejemplo:  
   cp .env.example .env

4. **Levantar el entorno (Docker/Sail):**  
   ./vendor/bin/sail up \-d

5. **Ejecutar migraciones:**  
   ./vendor/bin/sail artisan migrate

El sistema estará disponible en http://localhost.

## **🤝 Flujo de Trabajo (Gitflow)**

Para mantener el orden y la calidad del código, seguiremos estas reglas:

* **Rama main:** Protegida. Solo para despliegue a producción.  
* **Ramas feature/:** Crea siempre una rama para cada funcionalidad nueva (ej. feature/numpad-modal).  
* **Pull Requests:** Todo código debe pasar por una revisión antes de ser integrado.  
* **Mensajes de Commit:** Siguiendo el estándar *Conventional Commits* (feat:, fix:, chore:, docs:).

## **📚 Documentación de Referencia**

Todo el conocimiento del negocio se encuentra en la carpeta /docs:

* contexto\_proyecto\_claude.md: Reglas de negocio, gramajes e inventario.  
* estandares\_desarrollo.md: Convenciones de código y calidad.  
* mvp\_scope.md: Lista de tareas y sprints.

*Desarrollado para el crecimiento de "Donde Mechas".*