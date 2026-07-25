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

<style>
    /* ===== Layout Principal 70% Catálogo / 30% Tirilla (Desktop > 1024px) ===== */
    .pos-workspace {
        display: flex;
        flex-direction: row;
        gap: 16px;
        padding: 16px;
        min-height: 100vh;
        background-color: var(--pos-bg-main);
    }

    .pos-catalog-col {
        flex: 0 0 70%;
        max-width: 70%;
        display: flex;
        flex-direction: column;
    }

    .pos-ticket-col {
        flex: 0 0 30%;
        max-width: 30%;
    }

    .pos-ticket-panel {
        height: calc(100vh - 32px);
    }

    /* ===== Buscador ===== */
    .input-group-text {
        background-color: var(--pos-bg-surface) !important;
        border: 1px solid var(--pos-border-color) !important;
        border-right: none !important;
        border-radius: var(--radius-touch) 0 0 var(--radius-touch) !important;
    }
    .form-control-touch {
        border-radius: 0 var(--radius-touch) var(--radius-touch) 0 !important;
    }

    /* ===== Categorías: Tiles táctiles, altura mínima 60px ===== */
    .pos-category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }

    .pos-category-tile {
        min-height: 60px;
        background-color: var(--pos-bg-card);
        border: 2px solid var(--pos-border-color);
        border-radius: var(--radius-touch);
        color: var(--pos-text-main);
        font-weight: 700;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px;
        transition: all 0.15s ease;
    }

    .pos-category-tile:active {
        transform: scale(0.96);
    }

    .pos-category-tile.active {
        border-color: var(--pos-primary);
        background-color: rgba(255, 107, 0, 0.15);
        color: var(--pos-primary);
    }

    /* ===== Cuadrícula de Productos ===== */
    .pos-product-grid-wrapper {
        flex: 1;
        overflow-y: auto;
        padding-right: 4px;
    }

    .pos-product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    .pos-product-image {
        width: 100%;
        height: 70px;
        border-radius: 8px;
        background-color: var(--pos-bg-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: 8px;
        color: var(--pos-text-muted);
        font-size: 1.6rem;
    }

    .pos-product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pos-card-product {
        text-align: left;
        border: none;
        cursor: pointer;
    }

    .pos-empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
    }

    /* ===== Botones de Cantidad en Tirilla ===== */
    .pos-qty-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--pos-border-color);
        background-color: var(--pos-bg-surface);
        color: var(--pos-text-main);
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pos-qty-value {
        min-width: 28px;
        text-align: center;
        font-weight: 700;
        color: var(--pos-text-main);
    }

    .pos-qty-btn-danger {
        background-color: var(--pos-danger);
        color: #fff;
        border: none;
    }

    /* ===== Responsive: Tablet (768px - 1024px) ===== */
    @media (max-width: 1024px) {
        .pos-catalog-col,
        .pos-ticket-col {
            flex: 0 0 auto;
            max-width: none;
        }
        .pos-catalog-col { flex: 1 1 60%; }
        .pos-ticket-col { flex: 0 0 320px; }
        .pos-product-grid { grid-template-columns: repeat(3, 1fr); }
    }

    /* ===== Responsive: Mobile (< 768px) ===== */
    @media (max-width: 767px) {
        .pos-workspace {
            flex-direction: column;
            padding: 10px;
        }
        .pos-catalog-col,
        .pos-ticket-col {
            max-width: 100%;
            flex: 1 1 auto;
        }
        .pos-product-grid { grid-template-columns: repeat(2, 1fr); }
        .pos-category-grid {
            grid-auto-flow: column;
            grid-auto-columns: minmax(100px, auto);
            overflow-x: auto;
            grid-template-columns: none;
        }
        .pos-ticket-panel { height: auto; }
    }
</style>
