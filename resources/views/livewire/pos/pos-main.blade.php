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
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    class="form-control form-control-touch"
                    placeholder="Buscar por nombre o código de barras..."
                >
                @if($search)
                    <button type="button" class="btn btn-touch btn-touch-danger" wire:click="$set('search', '')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Navegación por Categorías (Tiles táctiles, min 60px alto) --}}
        <div class="pos-category-grid mb-3">
            <button
                type="button"
                wire:click="selectCategoria(null)"
                class="pos-category-tile {{ is_null($selectedCategoriaId) ? 'active' : '' }}"
            >
                <i class="fa-solid fa-border-all mb-1"></i>
                <span>Todos</span>
            </button>

            @foreach($categorias as $categoria)
                <button
                    type="button"
                    wire:click="selectCategoria({{ $categoria->id }})"
                    wire:key="categoria-{{ $categoria->id }}"
                    class="pos-category-tile {{ $selectedCategoriaId === $categoria->id ? 'active' : '' }}"
                >
                    <span>{{ $categoria->nombre }}</span>
                </button>
            @endforeach
        </div>

        {{-- Cuadrícula de Productos del negocio activo --}}
        <div class="pos-product-grid-wrapper">
            <div class="pos-product-grid">
                @forelse($productos as $producto)
                    <button
                        type="button"
                        wire:click="addToCart({{ $producto->id }})"
                        wire:key="producto-{{ $producto->id }}"
                        class="pos-card-product"
                    >
                        <div class="pos-product-image">
                            @if($producto->imagen_path)
                                <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->nombre }}">
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
                        <p class="small" style="color: var(--pos-text-muted);">Intenta con otro término o categoría.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ================================================= --}}
    {{-- COLUMNA DERECHA (30%) - TIRILLA / CARRITO ACTIVO   --}}
    {{-- ================================================= --}}
    <div class="pos-ticket-col">
        <div class="pos-ticket-panel">

            <div class="pos-ticket-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 font-brand text-white">
                    <i class="fa-solid fa-receipt me-2" style="color: var(--pos-primary);"></i>Pedido Activo
                </h5>
                @if(count($cart) > 0)
                    <button
                        wire:click="clearCart"
                        wire:confirm="¿Estás seguro de vaciar el pedido actual?"
                        class="btn btn-sm"
                        style="color: var(--pos-danger); border: 1px solid var(--pos-danger); border-radius: var(--radius-touch);"
                    >
                        Vaciar
                    </button>
                @endif
            </div>

            <div class="pos-ticket-body">
                @if(count($cart) > 0)
                    @foreach($cart as $item)
                        <div class="pos-ticket-item" wire:key="cart-item-{{ $item['id'] }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="fw-bold text-white pe-2">{{ $item['nombre'] }}</span>
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
                <button
                    type="button"
                    class="btn btn-touch btn-touch-primary w-100"
                    @if(count($cart) === 0) disabled @endif
                >
                    <i class="fa-solid fa-money-bill-1-wave"></i> COBRAR CONTADO
                </button>
            </div>

        </div>
    </div>

</div>
