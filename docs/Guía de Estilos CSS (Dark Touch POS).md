# **Guía de Estilos CSS y Sistema de Diseño: Dark Touch POS**

Este documento establece el estándar visual, las reglas de uso del sistema de diseño **Dark Touch** y las directrices de adaptabilidad *Responsive* (Mobile-First) para el POS y Ecosistema SaaS.

## **🎨 1\. Variables Globales y Colores (Design Tokens)**

Las variables están definidas en resources/css/app.css bajo el selector :root.

| Variable | Valor | Descripción / Uso |
| :---- | :---- | :---- |
| \--pos-bg-main | \#121417 | Fondo oscuro general de la aplicación. |
| \--pos-bg-card | \#1e2227 | Fondo de tarjetas de producto, módulos y paneles. |
| \--pos-bg-surface | \#282c34 | Superficie de cabeceras, entradas de texto y barras. |
| \--pos-border-color | \#323842 | Bordes sutiles de delimitación táctil. |
| \--pos-primary | \#ff6b00 | Color primario (Naranja Fuego) para acciones de cobro y acentos. |
| \--pos-primary-hover | \#e05e00 | Tono hover/active para acciones primarias. |
| \--pos-secondary | \#00b4d8 | Azul Cyán para apoyo y filtros secundarios. |
| \--pos-success | \#00e676 | Verde para transacciones aprobadas o ítems completados. |
| \--pos-danger | \#ff3838 | Rojo para cancelaciones, egresos de caja o alertas. |
| \--pos-warning | \#ffb300 | Amarillo/Naranja para ítems pendientes o en cocina. |
| \--pos-text-main | \#f8f9fa | Texto principal de alto contraste. |
| \--pos-text-muted | \#a0aec0 | Texto secundario o descripciones. |
| \--touch-btn-height | 54px | Altura mínima recomendada para interacción con dedos. |
| \--radius-touch | 12px | Bordes redondeados táctiles. |

## **🖐️ 2\. Componentes Táctiles Reutilizables**

### **A. Botones Táctiles (.btn-touch)**

Tienen una altura mínima de 54px, respuesta activa (active) al tacto y alta jerarquía visual para botones de acción principal (CTA).

\<\!-- Botón Primario Destacado (Login / Cobro / Confirmación) \--\>

\<button class="btn btn-touch btn-touch-primary w-100"\>

    \<i class="fa-solid fa-box-arrow-in-right"\>\</i\> Iniciar Sesión

\</button\>

\<\!-- Botón Éxito \--\>

\<button class="btn btn-touch btn-touch-success"\>

    \<i class="fa-solid fa-check"\>\</i\> Pedido Listo

\</button\>

\<\!-- Botón Peligro / Cancelar \--\>

\<button class="btn btn-touch btn-touch-danger"\>

    \<i class="fa-solid fa-trash"\>\</i\> Anular

\</button\>

#### **Regla CSS del Botón Primario CTA:**

.btn-touch-primary {

    background: linear-gradient(135deg, \#ff3838 0%, \#c0392b 100%);

    color: \#ffffff;

    border: none;

    border-radius: var(--radius-touch);

    padding: 16px 24px;

    font-size: 1.15rem;

    font-weight: 700;

    letter-spacing: 0.5px;

    text-transform: uppercase;

    box-shadow: 0 4px 15px rgba(255, 56, 56, 0.4);

    transition: all 0.2s ease-in-out;

}

.btn-touch-primary:hover, .btn-touch-primary:focus {

    background: linear-gradient(135deg, \#ff4d4d 0%, \#d63031 100%);

    box-shadow: 0 6px 20px rgba(255, 56, 56, 0.6);

    transform: translateY(-2px);

}

### **B. Campos de Texto y Formularios (.form-control-touch)**

Diseñados para alta legibilidad en pantallas táctiles y teclado virtual o físico.

\<div class="mb-3"\>

    \<label class="form-label text-light fw-semibold"\>Correo Electrónico\</label\>

    \<input type="email" class="form-control form-control-touch" placeholder="cajero@dondemechas.com"\>

\</div\>

### **C. Tarjeta de Producto POS (.pos-card-product)**

Módulo táctil para la cuadrícula del catálogo comercial.

\<div class="pos-card-product"\>

    \<span class="pos-product-title"\>Empanada de Carne\</span\>

    \<div class="d-flex justify-content-between align-items-center mt-2"\>

        \<span class="pos-product-price"\>$3.500\</span\>

        \<span class="badge bg-secondary"\>+ Agregar\</span\>

    \</div\>

\</div\>

### **D. Panel de Tirilla / Comanda (.pos-ticket-\*)**

Estructura de columna fija para la orden en curso.

\<div class="pos-ticket-panel"\>

    \<div class="pos-ticket-header"\>

        \<h5 class="m-0 font-brand"\>Pedido \#0012\</h5\>

    \</div\>

    \<div class="pos-ticket-body"\>

        \<div class="pos-ticket-item"\>

            \<div class="d-flex justify-content-between"\>

                \<span\>1x Hamburguesa Especial\</span\>

                \<span class="fw-bold"\>$17.000\</span\>

            \</div\>

        \</div\>

    \</div\>

    \<div class="pos-ticket-footer"\>

        \<button class="btn btn-touch btn-touch-primary w-100"\>Cobrar\</button\>

    \</div\>

\</div\>

### **E. Teclado Numérico Táctil (.pos-numpad)**

Grilla táctil de 3 columnas para ingreso de efectivo o cantidades.

\<div class="pos-numpad"\>

    \<button class="pos-numpad-btn"\>1\</button\>

    \<button class="pos-numpad-btn"\>2\</button\>

    \<button class="pos-numpad-btn"\>3\</button\>

    \<\!-- ... \--\>

    \<button class="pos-numpad-btn bg-danger text-white"\>C\</button\>

    \<button class="pos-numpad-btn"\>0\</button\>

    \<button class="pos-numpad-btn bg-success text-white"\>OK\</button\>

\</div\>

### **F. Métodos de Pago y Badges de Estado**

Selectores táctiles de método de pago e indicadores de comanda.

\<\!-- Métodos de Pago \--\>

\<div class="d-flex gap-2"\>

    \<button class="btn-pay-method active w-100"\>

        \<i class="fa-solid fa-money-bill-1-wave"\>\</i\> Efectivo

    \</button\>

    \<button class="btn-pay-method w-100"\>

        \<i class="fa-solid fa-mobile-screen"\>\</i\> Nequi / Transf.

    \</button\>

\</div\>

\<\!-- Badges de Estado \--\>

\<span class="badge badge-status-pending"\>Pendiente Cocina\</span\>

\<span class="badge badge-status-completed"\>Completado\</span\>

\<span class="badge badge-status-danger"\>Anulado\</span\>

### **G. Contenedores de Alerta y Errores (Tema Oscuro)**

Cajas de aviso con texto blanco brillante de alta legibilidad sobre fondos oscuros.

\<div class="alert alert-danger border border-danger mb-4 text-white" style="background-color: \#721c24; border-radius: 12px;"\>

    \<div class="d-flex align-items-center gap-2 mb-1"\>

        \<i class="bi bi-exclamation-triangle-fill text-warning"\>\</i\>

        \<strong class="text-white"\>Error de acceso\</strong\>

    \</div\>

    \<p class="mb-0 text-white"\>Las credenciales proporcionadas no son válidas.\</p\>

\</div\>

## **📌 3\. Reglas de Estilo del Proyecto**

1. **Prioridad Táctil:** Todo elemento interactivo debe tener un área de pulsación mínima de $48 \\times 48\\text{ px}$.  
2. **Sin Selección de Texto:** user-select: none está activo globalmente para evitar que el navegador resalte texto azul al tocar la pantalla de forma rápida y consecutiva.  
3. **Contraste Mínimo:** Los textos en pantalla oscura deben usar siempre \--pos-text-main (\#f8f9fa) o blanco puro para garantizar visibilidad rápida en entornos de cocina o caja de alto tráfico.

## **📱 4\. Estrategia Responsive y Adaptación Touch (Mobile-First)**

El sistema debe adaptarse de forma fluida a tres tipos de pantallas principales: **Smartphones** (meseros / toma rápida de pedido en mesa), **Tablets** (caja móvil / terminal táctil portátil) y **PC / Pantalla Tactil de Escritorio** (estación fija de cobro).

### **Breakpoints Oficiales**

* **Mobile (\< 768px):** Layout en 1 columna apilada o deslizable.  
* **Tablet (768px \- 1024px):** Layout híbrido de 2 columnas ajustables.  
* **Desktop (\> 1024px):** Layout estándar de 2 columnas fijas (70% Catálogo / 30% Tirilla).

### **Comportamiento por Pantalla en Terminal POS**

#### **1\. Dispositivos Móviles (\< 768px)**

* **Navegación del Carrito:** La tirilla de venta se oculta de la columna lateral y pasa a ser un **panel inferior desplegable (*Offcanvas / Bottom Drawer*)**.  
* **Barra Flotante Fija (*Sticky Bottom Bar*):** En la parte inferior de la pantalla se mantiene fija una barra con el total acumulado y un botón prominente: Ver Pedido ($XX.XXX).  
* **Grid de Productos:** 2 columnas de productos por fila (col-6).  
* **Categorías:** Fila superior con scroll horizontal táctil sin barra de desplazamiento visible (overflow-x: auto; white-space: nowrap).

#### **2\. Tablets (768px \- 1024px)**

* **Layout:** 2 columnas compactas.  
* **Grid de Productos:** 3 a 4 columnas por fila (col-md-4 o col-md-3).  
* **Carrito:** Columna lateral fija a la derecha con un ancho mínimo de 320px para asegurar la correcta lectura de la tirilla.

#### **3\. PC / Escritorio (\> 1024px)**

* **Layout:** Columna izquierda para catálogo (70%), Columna derecha fija para tirilla/pedido (30%).  
* **Grid de Productos:** 4 a 6 columnas por fila según la resolución de la pantalla.

### **Reglas de Ergonomía Táctil Móvil**

1. **Puntos de Toque (Touch Targets):** Mínimo $48\\times 48\\text{ px}$ en móviles para botones secundarios e inputs, y $56\\text{ px}$ para botones de acción principal (CTA).  
2. **Prevenir Selección Accidental:** Mantener la regla user-select: none; activa en tarjetas, numpads y botones para evitar selecciones de texto azucaradas por toques repetidos.  
3. **Modales y Numpad en Móvil:** En móviles, el modal de cobro y el teclado numérico deben desplegarse en pantalla completa (modal-fullscreen-sm-down) para facilitar el ingreso de efectivo y vueltas con una sola mano.

