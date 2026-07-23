# **Arquitectura SaaS Multi-Tenant: POS "Donde Mechas" & POS Engine**

Este documento describe la arquitectura de software, las estrategias de aislamiento de datos (*Multi-Tenancy*) y el modelo relacional implementado para escalar el sistema a un entorno SaaS (*Software as a Service*).

## **1\. Estrategia de Multi-Tenancy**

El sistema utiliza el patrón de **Multi-Tenancy por columna discriminadora (negocio\_id)** en un único esquema de base de datos PostgreSQL. Este enfoque garantiza alta eficiencia, escalabilidad simplificada y bajos costos de infraestructura en fases tempranas y de crecimiento.

                  ┌─────────────────────────────────────────┐  
                  │          PETICIÓN HTTP ENTRANTE         │  
                  └────────────────────┬────────────────────┘  
                                       │  
                                       ▼  
                  ┌─────────────────────────────────────────┐  
                  │       AUTENTICACIÓN & SESIÓN            │  
                  │ (Carga \`tenant\_id\` y \`user\_id\` activo)  │  
                  └────────────────────┬────────────────────┘  
                                       │  
                                       ▼  
                  ┌─────────────────────────────────────────┐  
                  │     GLOBAL ELOQUENT SCOPE (Tenant)      │  
                  │   Inyecta automáticamente en consulta:  │  
                  │      \`WHERE negocio\_id \= session\_id\`    │  
                  └────────────────────┬────────────────────┘  
                                       │  
            ┌──────────────────────────┴──────────────────────────┐  
            ▼                                                     ▼  
┌───────────────────────┐                             ┌───────────────────────┐  
│ Negocio A (ID: 1\)     │                             │ Negocio B (ID: 2\)     │  
│ \- Productos           │                             │ \- Productos           │  
│ \- Ventas / Turnos     │                             │ \- Ventas / Turnos     │  
│ \- Insumos / Recetas   │                             │ \- Insumos / Recetas   │  
└───────────────────────┘                             └───────────────────────┘

## **2\. Aislamiento Automático de Datos (Global Scopes)**

Para prevenir cualquier posibilidad de fuga de datos entre negocios (*Data Leakage*), todos los modelos Eloquent específicos de un tenant extienden un Trait o aplican un Global Scope llamado TenantScope.

### **Implementación del Global Scope:**

namespace App\\Models\\Scopes;

use Illuminate\\Database\\Eloquent\\Builder;  
use Illuminate\\Database\\Eloquent\\Model;  
use Illuminate\\Database\\Eloquent\\Scope;

class TenantScope implements Scope  
{  
    public function apply(Builder $builder, Model $model): void  
    {  
        if (session()-\>has('tenant\_id')) {  
            $builder-\>where($model-\>getTable() . '.negocio\_id', session('tenant\_id'));  
        }  
    }  
}

### **Inyección Automática en Modelos:**

Cada vez que se consulta o crea un registro mediante Eloquent (Producto::all(), Pedido::create(...)), el scope inyecta automáticamente el filtro negocio\_id \= session('tenant\_id') y asigna el ID de la empresa en la creación.

## **3\. Estado de Suscripción y Middleware (CheckTenantState)**

El acceso a las rutas operativas (/pos, /admin/\*) está custodiado por el middleware CheckTenantState.

* **Negocio Activo (activo \= true):** Acceso total permitido.  
* **Negocio Suspendido (activo \= false):** Redirección inmediata a una vista de "Suscripción Inactiva / Pago Pendiente" bloqueando cualquier operación de venta o consulta.

## **4\. Esquema Relacional de Base de Datos**

Las tablas principales del sistema están vinculadas jerárquicamente a la entidad madre negocios:

| Tabla | Clave Foránea Principal | Descripción / Rol en el Tenant |
| :---- | :---- | :---- |
| negocios | id (PK) | Entidad Tenant (Nombre, NIT, Plan, Estado activo). |
| users | negocio\_id | Usuarios del sistema (Admin, Cajero, Mesero) aislados por negocio. |
| categorias | negocio\_id | Secciones del menú (Hamburguesas, Salchipapas, Bebidas, etc.). |
| productos | negocio\_id, categoria\_id | Menú comercial de venta directa o combos. |
| insumos | negocio\_id | Inventario de materia prima (gramos, mililitros, unidades). |
| receta\_productos | producto\_id, insumo\_id | Tabla pivote de ingredientes para descuento automático de stock. |
| turnos\_caja | negocio\_id, user\_id | Control diario de caja (Monto inicial, Egresos, Cierre X/Z). |
| pedidos | negocio\_id, turno\_caja\_id | Registro de ventas de contado. |
| detalle\_pedidos | pedido\_id, producto\_id | Desglose de ítems comercializados por transacción. |

## **5\. Medidas de Seguridad y Buenas Prácticas**

1. **Jamás confiar en IDs enviados desde el cliente:** En Livewire o Controllers, no aceptar negocio\_id a través de formulaciones POST/wire:model. Siempre usar session('tenant\_id') o Auth::user()-\>negocio\_id.  
2. **Validación de Consultas Raw (DB::raw):** Evitar el uso de consultas SQL puras que omitan los scopes de Eloquent. Si se requiere SQL nativo, obligatoriamente incluir WHERE negocio\_id \= ?.  
3. **Pruebas de Aislamiento:** Crear tests unitarios en Pest/PHPUnit verificando que el Usuario A del Negocio 1 reciba error 403 Forbidden al intentar editar un recurso del Negocio 2\.