<div class="container-fluid py-3 min-vh-100 bg-dark text-white">
    <div class="row g-3">
        
        <!-- ========================================== -->
        <!-- SECCIÓN IZQUIERDA: CATÁLOGO DE PRODUCTOS   -->
        <!-- ========================================== -->
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="card bg-secondary text-white border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column">
                    
                    <!-- Buscador -->
                    <div class="mb-3">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-dark border-0 text-white-50">🔍</span>
                            <input 
                                type="text" 
                                wire:model.live.debounce.300ms="search" 
                                class="form-control bg-dark text-white border-0 shadow-none fs-5" 
                                placeholder="Buscar producto por nombre..."
                            >
                            @if($search)
                                <button class="btn btn-dark text-white-50" wire:click="$set('search', '')">✕</button>
                            @endif
                        </div>
                    </div>

                    <!-- Barra de Filtro por Categorías -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <!-- Botón Todas -->
                        <button 
                            type="button"
                            wire:click="selectCategoria(null)"
                            class="btn btn-sm px-3 rounded-pill fw-bold {{ is_null($selectedCategoriaId) ? 'btn-warning text-dark' : 'btn-outline-light' }}"
                        >
                            Todos
                        </button>

                        <!-- Botones de Categorías Dinámicas -->
                        @foreach($categorias as $categoria)
                            <button 
                                type="button"
                                wire:click="selectCategoria({{ $categoria->id }})"
                                class="btn btn-sm px-3 rounded-pill fw-bold {{ $selectedCategoriaId === $categoria->id ? 'btn-warning text-dark' : 'btn-outline-light' }}"
                            >
                                {{ $categoria->nombre }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Grilla de Productos -->
                    <div class="flex-grow-1 overflow-auto pe-1" style="max-height: calc(100vh - 180px);">
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-2">
                            @forelse($productos as $producto)
                                <div class="col">
                                    <button 
                                        type="button" 
                                        wire:click="addToCart({{ $producto->id }})"
                                        class="btn btn-outline-light w-100 h-100 p-3 text-start d-flex flex-column justify-content-between border-secondary-subtle bg-dark bg-gradient shadow-sm rounded-3 touch-card"
                                    >
                                        <div class="fw-bold fs-6 text-wrap mb-2 text-white">
                                            {{ $producto->nombre }}
                                        </div>
                                        <div class="text-warning fw-bolder fs-5">
                                            ${{ number_format($producto->precio, 0, ',', '.') }}
                                        </div>
                                    </button>
                                </div>
                            @empty
                                <div class="col-12 text-center py-5 text-white-50">
                                    <h5>No se encontraron productos</h5>
                                    <p class="small">Intenta con otro término de búsqueda.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SECCIÓN DERECHA: TICKET Y RESUMEN DE VENTA -->
        <!-- ========================================== -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card bg-secondary text-white border-0 shadow-sm h-100 d-flex flex-column">
                
                <!-- Encabezado del Ticket -->
                <div class="card-header bg-dark border-bottom border-secondary d-flex justify-content-between align-items-center py-3">
                    <h5 class="m-0 fw-bold">🛒 Pedido Activo</h5>
                    @if(count($cart) > 0)
                        <button 
                            wire:click="clearCart" 
                            wire:confirm="¿Estás seguro de vaciar el pedido actual?"
                            class="btn btn-sm btn-outline-danger"
                        >
                            Vaciar
                        </button>
                    @endif
                </div>

                <!-- Cuerpo: Lista de Ítems del Carrito -->
                <div class="card-body p-2 flex-grow-1 overflow-auto" style="max-height: calc(100vh - 350px); min-height: 250px;">
                    @if(count($cart) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($cart as $item)
                                <div class="list-group-item bg-dark text-white border-secondary rounded mb-2 p-2 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="fw-bold text-wrap pe-2">{{ $item['nombre'] }}</span>
                                        <span class="fw-bold text-warning">
                                            ${{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-white-50">
                                            ${{ number_format($item['precio'], 0, ',', '.') }} c/u
                                        </small>

                                        <!-- Botones de Control de Cantidad -->
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button 
                                                wire:click="updateQuantity({{ $item['id'] }}, 'decrease')" 
                                                class="btn btn-outline-light px-2"
                                            >-</button>
                                            <span class="btn btn-dark disabled text-white fw-bold px-3">
                                                {{ $item['cantidad'] }}
                                            </span>
                                            <button 
                                                wire:click="updateQuantity({{ $item['id'] }}, 'increase')" 
                                                class="btn btn-outline-light px-2"
                                            >+</button>
                                            <button 
                                                wire:click="removeFromCart({{ $item['id'] }})" 
                                                class="btn btn-danger px-2 ms-2 rounded-end"
                                                title="Eliminar ítem"
                                            >🗑</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-white-50 py-5">
                            <span class="fs-1 mb-2">📥</span>
                            <p class="m-0 text-center">El carrito está vacío.<br>Selecciona productos a la izquierda.</p>
                        </div>
                    @endif
                </div>

                <!-- Pie: Totales y Botón de Cobro de Contado -->
                <div class="card-footer bg-dark border-top border-secondary p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-white-50">Subtotal</span>
                        <span class="fs-5">${{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-4 fw-bold">TOTAL</span>
                        <span class="fs-3 fw-bolder text-warning">${{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <!-- Acciones de Cierre -->
                    <button 
                        type="button" 
                        class="btn btn-warning btn-lg w-100 fw-bold shadow py-3 text-dark fs-5"
                        @if(count($cart) === 0) disabled @endif
                    >
                        💵 COBRAR CONTADO
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Estilos CSS complementarios -->
<style>
    .touch-card {
        transition: transform 0.1s ease, border-color 0.2s ease;
        min-height: 100px;
    }
    .touch-card:active {
        transform: scale(0.96);
    }
    .touch-card:hover {
        border-color: #ffc107 !important;
    }
</style>