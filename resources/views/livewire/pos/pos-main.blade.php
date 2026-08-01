<div class="pos-workspace">

    {{-- ================================================= --}}
    {{-- COLUMNA IZQUIERDA (70%) - CATÁLOGO DE PRODUCTOS    --}}
    {{-- ================================================= --}}
    <div class="pos-catalog-col">

        {{-- Buscador dinámico (nombre o código de barras) --}}
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa-solid fa-magnifying-glass" style="color: var(--pos-text-muted);"></i>
                </span>
                {{-- El lector de códigos de barras teclea el código y manda Enter --}}
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    wire:keydown.enter.prevent="agregarPorCodigoDeBarras"
                    class="form-control form-control-touch"
                    placeholder="Buscar o escanear código de barras..."
                    autocomplete="off"
                    autofocus
                >
                @if($search)
                    <button type="button" class="btn btn-touch btn-touch-danger" wire:click="$set('search', '')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Navegación por Etiquetas (Tiles táctiles, min 60px alto) --}}
        <div class="pos-etiqueta-grid mb-3">
            <button
                type="button"
                wire:click="seleccionarEtiqueta(null)"
                class="pos-etiqueta-tile {{ is_null($etiquetaSeleccionadaId) ? 'active' : '' }}"
            >
                <i class="fa-solid fa-border-all mb-1"></i>
                <span>Todos</span>
            </button>

            @foreach($etiquetas as $etiqueta)
                <button
                    type="button"
                    wire:click="seleccionarEtiqueta({{ $etiqueta->id }})"
                    wire:key="etiqueta-{{ $etiqueta->id }}"
                    class="pos-etiqueta-tile {{ $etiquetaSeleccionadaId === $etiqueta->id ? 'active' : '' }}"
                >
                    <span>{{ $etiqueta->nombre }}</span>
                </button>
            @endforeach
        </div>

        {{-- Cuadrícula de Productos del negocio activo --}}
        <div class="pos-product-grid-wrapper">
            <div class="pos-product-grid">
                @forelse($productos as $producto)
                    {{-- wire:loading.attr deshabilita la cuadrícula mientras se registra
                         un producto: en tablet lenta el doble toque duplicaba la línea --}}
                    <button
                        type="button"
                        wire:click="addToCart({{ $producto->id }})"
                        wire:key="producto-{{ $producto->id }}"
                        wire:loading.attr="disabled"
                        wire:target="addToCart"
                        class="pos-card-product"
                    >
                        <div class="pos-product-image">
                            @if($producto->imagen_path)
                                <img src="{{ asset('storage/' . $producto->imagen_path) }}"
                                     alt="{{ $producto->nombre }}"
                                     loading="lazy">
                            @else
                                <i class="fa-solid fa-utensils"></i>
                            @endif
                        </div>
                        <span class="pos-product-title">{{ $producto->nombre }}</span>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="pos-product-price">${{ number_format($producto->precio, 0, ',', '.') }}</span>
                            <span class="badge" style="background-color: var(--pos-secondary);">+ Agregar</span>
                        </div>
                    </button>
                @empty
                    <div class="pos-empty-state">
                        <i class="fa-solid fa-magnifying-glass fs-1 mb-3 d-block" style="color: var(--pos-text-muted);"></i>
                        <h5 class="text-white">No se encontraron productos</h5>
                        <p class="small" style="color: var(--pos-text-muted);">Intenta con otro término o etiqueta.</p>
                    </div>
                @endforelse
            </div>

            @if($limiteAlcanzado)
                <p class="small text-center mt-3 mb-0" style="color: var(--pos-text-muted);">
                    Se muestran los primeros {{ $productos->count() }} productos. Afina la búsqueda para ver el resto.
                </p>
            @endif
        </div>

    </div>

    {{-- ================================================= --}}
    {{-- COLUMNA DERECHA (30%) - TIRILLA / CARRITO ACTIVO   --}}
    {{--                                                    --}}
    {{-- offcanvas-md: por debajo de 768px la tirilla se     --}}
    {{-- convierte en panel inferior desplegable; de ahí     --}}
    {{-- para arriba es una columna normal.                  --}}
    {{--                                                    --}}
    {{-- wire:ignore.self evita que Livewire borre la clase   --}}
    {{-- "show" que Bootstrap añade al abrir: sin esto el     --}}
    {{-- panel se cerraría solo al tocar + o -.               --}}
    {{-- ================================================= --}}
    <div
        class="pos-ticket-col offcanvas-md offcanvas-bottom"
        tabindex="-1"
        id="posTicketPanel"
        aria-labelledby="posTicketLabel"
        wire:ignore.self
    >
        <div class="pos-ticket-panel">

            <div class="pos-ticket-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 font-brand text-white" id="posTicketLabel">
                    <i class="fa-solid fa-receipt me-2" style="color: var(--pos-primary);"></i>Pedido Activo
                </h5>
                <div class="d-flex align-items-center gap-2">
                    @if($lineas->isNotEmpty())
                        <button
                            wire:click="clearCart"
                            wire:confirm="¿Estás seguro de vaciar el pedido actual?"
                            class="btn btn-sm"
                            style="color: var(--pos-danger); border: 1px solid var(--pos-danger); border-radius: var(--radius-touch);"
                        >
                            Vaciar
                        </button>
                    @endif
                    <button
                        type="button"
                        class="btn-close btn-close-white d-md-none"
                        data-bs-dismiss="offcanvas"
                        data-bs-target="#posTicketPanel"
                        aria-label="Cerrar pedido"
                    ></button>
                </div>
            </div>

            <div class="pos-ticket-body">
                @if($lineas->isNotEmpty())
                    @foreach($lineas as $item)
                        <div class="pos-ticket-item" wire:key="cart-item-{{ $item['id'] }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="fw-bold text-white pe-2">
                                    {{ $item['nombre'] }}
                                    @unless($item['disponible'])
                                        {{-- Se puede cobrar igual: lo más probable es que ya esté
                                             servido. Queda registrado en la línea del pedido. --}}
                                        <span class="badge badge-status-pending d-block mt-1">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Ya no disponible
                                        </span>
                                    @endunless
                                </span>
                                <span class="fw-bold" style="color: var(--pos-primary);">
                                    ${{ number_format($item['subtotal'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small style="color: var(--pos-text-muted);">
                                    ${{ number_format($item['precio'], 0, ',', '.') }} c/u
                                </small>
                                <div class="d-flex align-items-center gap-1">
                                    <button wire:click="updateQuantity({{ $item['id'] }}, 'decrease')" class="pos-qty-btn">-</button>
                                    <span class="pos-qty-value">{{ $item['cantidad'] }}</span>
                                    <button wire:click="updateQuantity({{ $item['id'] }}, 'increase')" class="pos-qty-btn">+</button>
                                    <button wire:click="removeFromCart({{ $item['id'] }})" class="pos-qty-btn pos-qty-btn-danger" title="Eliminar ítem">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="pos-empty-state h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fa-solid fa-cart-shopping fs-1 mb-3" style="color: var(--pos-text-muted);"></i>
                        <p class="m-0 text-center" style="color: var(--pos-text-muted);">
                            El carrito está vacío.<br>Selecciona productos a la izquierda.
                        </p>
                    </div>
                @endif
            </div>

            <div class="pos-ticket-footer">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="color: var(--pos-text-muted);">Subtotal</span>
                    <span class="fs-5 text-white">${{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-4 fw-bold text-white">TOTAL</span>
                    <span class="fs-3 fw-bolder" style="color: var(--pos-primary);">${{ number_format($total, 0, ',', '.') }}</span>
                </div>
                @if($mensajeExito)
                    <div class="alert admin-alerta-exito d-flex align-items-center gap-2 py-2" role="alert">
                        <i class="fa-solid fa-circle-check"></i>{{ $mensajeExito }}
                    </div>
                @endif

                @if($mensajeError)
                    <div class="alert admin-alerta-error d-flex align-items-start gap-2 py-2" role="alert">
                        <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                        <span>{{ $mensajeError }}</span>
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="abrirCobro"
                    class="btn btn-touch btn-touch-primary w-100"
                    @disabled($lineas->isEmpty())
                >
                    <i class="fa-solid fa-money-bill-1-wave"></i> COBRAR CONTADO
                </button>
            </div>

        </div>
    </div>

    {{-- ================================================= --}}
    {{-- BARRA INFERIOR FIJA (solo móvil)                   --}}
    {{-- Mantiene el total a la vista y abre la tirilla, que --}}
    {{-- en móvil ya no está en pantalla.                    --}}
    {{-- ================================================= --}}
    <div class="pos-mobile-bar d-md-none">
        <button
            type="button"
            class="btn btn-touch btn-touch-primary w-100"
            data-bs-toggle="offcanvas"
            data-bs-target="#posTicketPanel"
            aria-controls="posTicketPanel"
            @disabled($lineas->isEmpty())
        >
            <i class="fa-solid fa-receipt"></i>
            @if($lineas->isNotEmpty())
                Ver Pedido ({{ $lineas->sum('cantidad') }}) &mdash; ${{ number_format($total, 0, ',', '.') }}
            @else
                Carrito vacío
            @endif
        </button>
    </div>

    {{-- ================================================= --}}
    {{-- DIÁLOGO DE COBRO                                   --}}
    {{-- Mientras está abierto el carrito queda bloqueado.  --}}
    {{-- Cancelar lo devuelve intacto: nunca salió de aquí. --}}
    {{-- ================================================= --}}
    @if($cobrando)
        <div class="pos-cobro-fondo" wire:key="dialogo-cobro">
            <div class="pos-cobro" role="dialog" aria-modal="true" aria-labelledby="tituloCobro">

                <div class="pos-cobro-cabecera">
                    <div>
                        <h2 class="m-0 font-brand text-white" id="tituloCobro">Cobrar</h2>
                        <span class="small" style="color: var(--pos-text-muted);">
                            {{ $lineas->sum('cantidad') }} ítems
                        </span>
                    </div>
                    <button type="button" class="btn-close btn-close-white"
                            wire:click="cerrarCobro" aria-label="Cancelar cobro"></button>
                </div>

                <div class="pos-cobro-total">
                    <span>TOTAL A PAGAR</span>
                    <strong>${{ number_format($total, 0, ',', '.') }}</strong>
                </div>

                <div class="pos-cobro-cuerpo">
                    {{-- Método de pago --}}
                    <div class="pos-metodos">
                        @foreach(\App\Support\RegistroDeVenta::metodosDePago() as $metodo)
                            <button type="button" wire:click="seleccionarMetodo('{{ $metodo }}')"
                                    class="btn-pay-method {{ $metodoPago === $metodo ? 'active' : '' }}">
                                @if($metodo === 'efectivo')
                                    <i class="fa-solid fa-money-bill-1-wave d-block mb-1"></i>Efectivo
                                @elseif($metodo === 'tarjeta')
                                    <i class="fa-solid fa-credit-card d-block mb-1"></i>Tarjeta
                                @else
                                    <i class="fa-solid fa-mobile-screen d-block mb-1"></i>{{ ucfirst($metodo) }}
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @if($metodoPago === 'efectivo')
                        @unless($hayTurnoAbierto)
                            <div class="alert admin-alerta-error d-flex align-items-start gap-2 py-2 mt-3 mb-0">
                                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                                <span>No hay turno de caja abierto. Ábrelo antes de cobrar en efectivo.</span>
                            </div>
                        @endunless

                        {{-- El campo acepta el teclado físico; el numpad escribe
                             en esta misma propiedad. Un solo camino, dos formas. --}}
                        <label class="form-label text-light fw-semibold mt-3" for="recibido">Efectivo recibido</label>
                        <input id="recibido" type="text" inputmode="numeric" autocomplete="off" autofocus
                               wire:model.live="efectivoRecibido"
                               wire:keydown.enter.prevent="confirmarCobro"
                               wire:keydown.escape.prevent="cerrarCobro"
                               class="form-control form-control-touch pos-cobro-monto"
                               placeholder="0">

                        <div class="pos-cobro-atajos">
                            <button type="button" wire:click="montoExacto" class="pos-atajo">Exacto</button>
                            @foreach([5000, 10000, 20000, 50000] as $billete)
                                <button type="button" wire:click="pulsar('{{ $billete }}')" class="pos-atajo">
                                    ${{ number_format($billete, 0, ',', '.') }}
                                </button>
                            @endforeach
                        </div>

                        <div class="pos-numpad mt-3">
                            @foreach([1,2,3,4,5,6,7,8,9] as $numero)
                                <button type="button" wire:click="pulsar('{{ $numero }}')" class="pos-numpad-btn">{{ $numero }}</button>
                            @endforeach
                            <button type="button" wire:click="pulsar('00')" class="pos-numpad-btn">00</button>
                            <button type="button" wire:click="pulsar('0')" class="pos-numpad-btn">0</button>
                            <button type="button" wire:click="borrarDigito" class="pos-numpad-btn">
                                <i class="fa-solid fa-delete-left"></i>
                            </button>
                        </div>

                        <div class="pos-cobro-cambio {{ $faltante > 0 ? 'insuficiente' : '' }}">
                            @if($faltante > 0)
                                <span>Faltan</span>
                                <strong>${{ number_format($faltante, 0, ',', '.') }}</strong>
                            @else
                                <span>Cambio</span>
                                <strong>${{ number_format($cambio, 0, ',', '.') }}</strong>
                            @endif
                        </div>
                    @else
                        <p class="m-0 mt-3" style="color: var(--pos-text-muted);">
                            El importe es exacto: confirma cuando la transacción esté aprobada.
                        </p>
                    @endif

                    @if($mensajeError)
                        <div class="alert admin-alerta-error d-flex align-items-start gap-2 py-2 mt-3 mb-0">
                            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                            <span>{{ $mensajeError }}</span>
                        </div>
                    @endif
                </div>

                <div class="pos-cobro-pie">
                    <button type="button" wire:click="cerrarCobro" class="btn btn-touch admin-btn-secundario">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarCobro"
                            wire:loading.attr="disabled" wire:target="confirmarCobro"
                            class="btn btn-touch btn-touch-primary flex-grow-1"
                            @disabled($metodoPago === 'efectivo' && $faltante > 0)>
                        <i class="fa-solid fa-check"></i> Confirmar cobro
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
