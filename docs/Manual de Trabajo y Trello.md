# **Manual de Flujo de Trabajo y Tablero Trello: POS SaaS**

Este documento establece la metodología de trabajo, el protocolo de integración de código y la guía de creación de tarjetas para el tablero de **Trello** del equipo de desarrollo.

## **🚦 Flujo del Tablero Trello**

El tablero está organizado en 5 columnas principales que representan el ciclo de vida de cada tarea:

┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐  
│ 1\. BACKLOG  │──\>│ 2\. PENDIENTE│──\>│ 3\. EN       │──\>│ 4\. REVISIÓN │──\>│ 5\. HECHO    │  
│    IDEAS    │   │    PRIORIZ. │   │    PROCESO  │   │    / PR     │   │    (DONE)   │  
└─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘

1. **Backlog / Ideas:** Requerimientos futuros, mejoras no prioritarias o nuevas fases del SaaS.  
2. **Pendiente / Sprint Actual:** Tarjetas refinadas y listas para ser tomadas por los desarrolladores.  
3. **En Proceso (In Progress):** Tarea en ejecución activa. *(Máximo 1 o 2 tarjetas activas por desarrollador para evitar cuellos de botella).*  
4. **Revisión / PR (Pull Request):** Código terminado, probado localmente y subido en una rama feature/ listo para Code Review del Lead Developer.  
5. **Completado (Done):** Tarea aprobada e integrada en la rama main.

## **🏷️ Sistema de Etiquetas en Trello**

| Etiqueta | Color | Uso / Ámbito |
| :---- | :---- | :---- |
| Frontend | Azul Claro | Maquetación Blade, HTML, CSS y diseño táctil. |
| Backend | Verde | Lógica PHP, Servicios, Controladores y Negocio. |
| Livewire | Rosado | Componentes reactivos en tiempo real. |
| UI/UX Touch | Naranja | Ergonomía táctil, Numpad, Modales y Responsive. |
| Database | Morado | Migraciones, Seeders, Eloquent y Scopes. |
| Security | Rojo | Autenticación, Middleware y Aislamiento Tenant. |

## **📇 Plantillas de Tarjetas para Importar a Trello**

A continuación se presentan los textos completos formateados para copiar y pegar directamente al crear las tarjetas prioritarias:

### **Tarjeta 1: Componente Livewire PosMain (Pantalla de Ventas)**

* **Asignado:** Desarrollador Frontend / Livewire  
* **Etiquetas:** Frontend, Livewire, UI/UX Touch

**Descripción:**

OBJETIVO: Crear la interfaz principal de la terminal de ventas táctil optimizada para pantallas táctiles (Touch POS).

REQUISITOS Y ESPECIFICACIONES TÉCNICO-VISUALES:  
1\. Estructura Layout: Layout de 2 columnas principales.  
   \- Columna Izquierda (70%): Cuadrícula de productos y selector de categorías.  
   \- Columna Derecha (30%): Panel lateral de la tirilla de venta / carrito de compras activo.  
2\. Navegación por Categorías:  
   \- Renderizar categorías activas del negocio en un grid de tiles táctiles de al menos 60px de alto.  
   \- Filtrar productos en tiempo real al hacer tap en una categoría.  
3\. Cuadrícula de Productos:  
   \- Renderizar solo productos del negocio\_id activo en sesión.  
   \- Tarjeta de producto: Nombre, Precio ($XX.XXX) e Imagen/Placeholder.  
   \- Buscador dinámico superior (wire:model.live) por nombre o código.  
4\. Responsive:  
   \- En móviles (\<768px), la tirilla colapsa en una barra flotante inferior (Sticky Bottom Bar) que abre un panel Offcanvas.  
5\. Estilos: Aplicar estrictamente la guía Dark Touch (appq.css / docs/guia\_estilos\_css.md).

### **Tarjeta 2: Lógica del Carrito de Compras en Memoria**

* **Asignado:** Lead / Core Developer  
* **Etiquetas:** Backend, Livewire, Database

**Descripción:**

OBJETIVO: Implementar la lógica del estado del carrito dentro del componente Livewire \`PosMain\` antes de persistir la orden en base de datos.

ESPECIFICACIONES TÉCNICAS:  
1\. Propiedades:  
   \- Definir propiedad pública $cart \= \[\] en Livewire.  
2\. Métodos Requeridos:  
   \- addToCart($productoId): Incrementa cantidad si existe, o agrega con precio unitario.  
   \- updateQuantity($productoId, $opcion): Suma/resta cantidad (+ / \-). Elimina si llega a 0\.  
   \- removeFromCart($productoId): Elimina el ítem del carrito.  
   \- clearCart(): Vacía el carrito.  
3\. Cálculos en Tiempo Real:  
   \- Getters/Computados para subtotal, impuestos y total.  
4\. Seguridad:  
   \- Validar que $productoId pertenezca al negocio\_id del usuario autenticado.

### **Tarjeta 3: Modal Táctil de Cobro y Teclado Numérico (Numpad)**

* **Asignado:** Desarrollador UI/UX  
* **Etiquetas:** UI/UX Touch, Frontend, Livewire

**Descripción:**

OBJETIVO: Diseñar e integrar la ventana modal de procesamiento de pago optimizada para entrada táctil rápida.

ESPECIFICACIONES TÉCNICO-VISUALES:  
1\. Modal de Cobro (Bootstrap Modal \+ Livewire):  
   \- Se activa desde el botón CTA "PAGAR" de la tirilla.  
   \- Muestra el TOTAL A PAGAR en tamaño gigante.  
2\. Métodos de Pago:  
   \- Selector de botones táctiles: Efectivo, Nequi, Daviplata, Tarjeta.  
3\. Teclado Numérico Táctil (Numpad):  
   \- Botones gigantes (0-9, 00, C, Backspace) \+ denominaciones rápidas ($10k, $20k, $50k, Pago Exacto).  
4\. Cambio / Vueltas:  
   \- Calcula y muestra el cambio recibido en tiempo real.  
   \- El botón "CONFIRMAR VENTA" solo se habilita si el pago recibido \>= Total.  
5\. Responsive:  
   \- Usar modal-fullscreen-sm-down en smartphones para facilitar operación a una mano.

### **Tarjeta 4: Persistencia de Venta e Inventario**

* **Asignado:** Lead / Core Developer  
* **Etiquetas:** Backend, Database

**Descripción:**

OBJETIVO: Procesar la transacción final en la base de datos dentro de una transacción segura (DB::transaction) y descontar existencias.

ESPECIFICACIONES TÉCNICAS:  
1\. Crear Servicio: App\\Services\\ProcesarVentaService.  
2\. Inserción de Datos:  
   \- Tabla pedidos: negocio\_id, user\_id, monto\_total, metodo\_pago, pago\_recibido, cambio, estado.  
   \- Tabla detalle\_pedidos: Guardar ítems del carrito (producto\_id, cantidad, precio\_unitario, subtotal).  
3\. Descuento de Stock:  
   \- Descontar existencias en la tabla productos o insumos (a través de receta\_productos).  
4\. Cierre:  
   \- Emitir evento Livewire para resetear carrito, cerrar modal y disparar notificación Toast.

## **🤝 Protocolo de Git & Commits**

Para mantener el historial de versión limpio:

* **Creación de Ramas:** feature/nombre-funcionalidad (Ej. feature/pos-numpad-modal).  
* **Mensajes de Commit (Conventional Commits):**  
  * feat: ... (Nueva característica)  
  * fix: ... (Corrección de error)  
  * style: ... (Ajustes visuales/CSS sin afectar lógica)  
  * docs: ... (Actualizaciones de documentación)  
* **Pull Requests (PR):** Ningún desarrollador hace commit directo a main. Todo cambio requiere PR y aprobación del Lead Developer.