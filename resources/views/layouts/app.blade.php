<!-- Google Fonts: Montserrat & Rubik -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- FontAwesome 6 Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

@livewireStyles

<style>
    :root {
        --bs-body-font-family: 'Rubik', sans-serif;
        --brand-primary: #ff5722;   /* Naranja Fuego */
        --brand-secondary: #ffc107; /* Amarillo Cheddar */
        --brand-dark: #121212;      /* Negro Fondo Táctil */
        --brand-surface: #1e1e1e;   /* Superficie Tarjetas */
        --brand-border: #2d2d2d;
    }

    body {
        background-color: var(--brand-dark);
        color: #f8f9fa;
        user-select: none; /* Previene selección accidental en pantallas táctiles */
        -webkit-user-select: none;
        overflow-x: hidden;
    }

    .font-brand {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
    }

    /* Header / Navbar Operativa */
    .pos-header {
        background-color: #161616;
        border-bottom: 2px solid var(--brand-border);
        height: 65px;
    }

    /* Estilos Táctiles Globales (Tiles & Buttons) */
    .btn-touch {
        min-height: 55px;
        font-weight: 700;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: transform 0.05s ease, background-color 0.15s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }

    .btn-touch:active {
        transform: scale(0.96);
    }

    /* Botón de Login Destacado con Alto Impacto Visual */
    .btn-touch-primary {
        background: linear-gradient(135deg, #ff3838 0%, #c0392b 100%) !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 16px 24px !important;
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(255, 56, 56, 0.4) !important;
        transition: all 0.2s ease-in-out !important;
    }

    /* Efectos Táctiles / Hover */
    .btn-touch-primary:hover, 
    .btn-touch-primary:focus {
        background: linear-gradient(135deg, #ff4d4d 0%, #d63031 100%) !important;
        box-shadow: 0 6px 20px rgba(255, 56, 56, 0.6) !important;
        transform: translateY(-2px);
    }

    .btn-touch-primary:active {
        transform: translateY(1px);
        box-shadow: 0 2px 8px rgba(255, 56, 56, 0.3) !important;
    }

    /* Categorías y Productos (Tiles) */
    .tile-category {
        background-color: var(--brand-surface);
        border: 2px solid var(--brand-border);
        color: #fff;
        border-radius: 14px;
        padding: 12px 16px;
        font-weight: 700;
        font-size: 1.05rem;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s ease;
    }

    .tile-category.active, .tile-category:hover {
        border-color: var(--brand-primary);
        background-color: rgba(255, 87, 34, 0.15);
        color: var(--brand-primary);
    }

    .tile-product {
        background-color: var(--brand-surface);
        border: 1px solid var(--brand-border);
        border-radius: 16px;
        padding: 14px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.15s ease;
        position: relative;
        overflow: hidden;
    }

    .tile-product:hover {
        border-color: var(--brand-secondary);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255, 193, 7, 0.15);
    }

    .tile-product:active {
        transform: scale(0.97);
    }

    .product-price-badge {
        background-color: var(--brand-primary);
        color: #ffffff;
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    /* Custom Scrollbar para el POS */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #181818;
    }
    ::-webkit-scrollbar-thumb {
        background: #333;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--brand-primary);
    }
</style>

<!-- BARRA SUPERIOR OPERATIVA (POS HEADER) -->
<header class="pos-header d-flex align-items-center justify-content-between px-3 sticky-top">
    
    <!-- Branding e Indicador de Local -->
    <div class="d-flex align-items-center gap-3">
        <div class="bg-warning text-dark rounded-3 px-2 py-1 font-brand fs-5">
            🔥 DM
        </div>
        <div>
            <h1 class="h6 mb-0 font-brand text-uppercase tracking-wider text-light">DONDE MECHAS</h1>
            <small class="text-warning fw-bold" style="font-size: 0.72rem;">¡Donde la porción sí se respeta!</small>
        </div>
    </div>

    <!-- Estado de Caja & Reloj -->
    <div class="d-flex align-items-center gap-3">
        <!-- Reloj en vivo -->
        <div class="d-none d-md-flex align-items-center text-muted gap-2 px-3 py-1 bg-dark rounded-pill border border-secondary border-opacity-25 fs-7">
            <i class="fa-regular fa-clock text-warning"></i>
            <span id="pos-clock" class="fw-bold text-light">00:00:00 PM</span>
        </div>

        <!-- Botón de Egreso Rápido de Caja -->
        <button class="btn btn-danger btn-touch px-3 py-1 fs-7" data-bs-toggle="modal" data-bs-target="#modalEgresoCaja">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <span class="d-none d-sm-inline">Salida Caja</span>
        </button>

        <!-- Indicador de Turno de Caja -->
        <div class="d-flex align-items-center gap-2 bg-dark px-3 py-1.5 rounded-3 border border-secondary border-opacity-25">
            <div class="spinner-grow spinner-grow-sm text-success" role="status"></div>
            <div class="lh-1">
                <span class="d-block text-muted" style="font-size: 0.65rem;">CAJA ABIERTA</span>
                <span class="fw-bold text-success fs-7">$150.000 (Base)</span>
            </div>
        </div>
    </div>

    <!-- Perfil Cajero & Cierre -->
    <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary border-0 text-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-circle-user fs-5 text-warning"></i>
                <span class="d-none d-lg-inline font-brand fs-7">{{ Auth::user()->nombre ?? 'Cajero' }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-cash-register me-2 text-warning"></i>Cierre X / Z</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- CONTENIDO PRINCIPAL (POS CORE WORKSPACE) -->
<main class="flex-grow-1 overflow-hidden">
    @yield('content')
</main>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@livewireScripts

<script>
    // Reloj en tiempo real para el turno
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const clockEl = document.getElementById('pos-clock');
        if (clockEl) clockEl.innerText = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
