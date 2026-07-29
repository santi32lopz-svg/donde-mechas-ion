<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    {{-- Terminal táctil: se bloquea el pinch-zoom para evitar zooms accidentales durante el cobro --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#121417">

    <title>@yield('title', config('app.name', 'POS Donde Mechas'))</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Bootstrap, FontAwesome, tipografías y el sistema de diseño "Dark Touch"
         van empaquetados por Vite, no por CDN: el POS debe abrir sin internet. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- BARRA SUPERIOR OPERATIVA (POS HEADER) -->
    <header class="pos-header d-flex align-items-center justify-content-between px-3 sticky-top">

        <!-- Branding e Indicador de Local -->
        <div class="d-flex align-items-center gap-3">
            <div class="pos-header-logo font-brand fs-5">
                🔥 DM
            </div>
            <div>
                <h1 class="h6 mb-0 font-brand text-uppercase text-light">{{ session('tenant_nombre', 'Donde Mechas') }}</h1>
                <small class="fw-bold" style="font-size: 0.72rem; color: var(--pos-warning);">¡Donde la porción sí se respeta!</small>
            </div>
        </div>

        <!-- Estado de Caja & Reloj -->
        <div class="d-flex align-items-center gap-3">
            <!-- Reloj en vivo -->
            <div class="pos-header-chip d-none d-md-flex align-items-center gap-2">
                <i class="fa-regular fa-clock" style="color: var(--pos-warning);"></i>
                <span id="pos-clock" class="fw-bold text-light">--:--:--</span>
            </div>

            <!-- Botón de Egreso Rápido de Caja -->
            <button class="btn btn-touch btn-touch-danger px-3" data-bs-toggle="modal" data-bs-target="#modalEgresoCaja">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span class="d-none d-sm-inline">Salida Caja</span>
            </button>

            <!-- Indicador de Turno de Caja -->
            <div class="pos-header-chip d-none d-lg-flex align-items-center gap-2">
                <div class="spinner-grow spinner-grow-sm" style="color: var(--pos-success);" role="status">
                    <span class="visually-hidden">Turno activo</span>
                </div>
                <div class="lh-1">
                    <span class="d-block" style="font-size: 0.65rem; color: var(--pos-text-muted);">CAJA ABIERTA</span>
                    <span class="fw-bold" style="color: var(--pos-success);">$150.000 (Base)</span>
                </div>
            </div>
        </div>

        <!-- Perfil Cajero & Cierre -->
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary border-0 text-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-circle-user fs-5" style="color: var(--pos-warning);"></i>
                    <span class="d-none d-lg-inline font-brand">{{ Auth::user()->nombre ?? 'Cajero' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
                    <li><a class="dropdown-item" href="#"><i class="fa-solid fa-cash-register me-2" style="color: var(--pos-warning);"></i>Cierre X / Z</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="color: var(--pos-danger);">
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
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts

    <script>
        // Reloj en tiempo real para el turno de caja
        (function () {
            const clockEl = document.getElementById('pos-clock');
            if (!clockEl) return;

            function updateClock() {
                clockEl.textContent = new Date().toLocaleTimeString('es-CO', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
                });
            }

            updateClock();
            setInterval(updateClock, 1000);
        })();
    </script>
</body>
</html>
