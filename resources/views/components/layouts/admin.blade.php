@php
    $usuario = auth()->user();
    $negocioActivo = app(\App\Support\TenantContext::class)->negocio();

    // El selector solo tiene sentido si hay entre qué elegir.
    $negociosDisponibles = $usuario->negociosVisibles()->orderBy('nombre')->limit(20)->get();

    $secciones = [
        ['ruta' => 'admin.dashboard',     'icono' => 'fa-gauge-high',     'texto' => 'Dashboard'],
        ['ruta' => 'admin.mi-negocio',    'icono' => 'fa-store',          'texto' => 'Mi Negocio'],
        ['ruta' => 'admin.productos',     'icono' => 'fa-burger',         'texto' => 'Productos'],
        ['ruta' => 'admin.etiquetas',     'icono' => 'fa-tags',           'texto' => 'Etiquetas'],
        ['ruta' => 'admin.pedidos',       'icono' => 'fa-receipt',        'texto' => 'Pedidos'],
        ['ruta' => 'admin.caja',          'icono' => 'fa-cash-register',  'texto' => 'Caja'],
        ['ruta' => 'admin.usuarios',      'icono' => 'fa-users',          'texto' => 'Usuarios'],
        ['ruta' => 'admin.configuracion', 'icono' => 'fa-gear',           'texto' => 'Configuración'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#121417">

    <title>{{ $title ?? 'Administración' }} · {{ $negocioActivo?->nombre ?? 'POS SaaS' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="admin-body">

    {{-- ============ BARRA SUPERIOR ============ --}}
    <header class="admin-topbar d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center gap-3">
            <button class="btn admin-toggle d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#adminSidebar"
                    aria-controls="adminSidebar" aria-label="Abrir menú">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="font-brand text-uppercase text-light d-none d-sm-inline">Administración</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            {{-- Selector de negocio: cambiar de contexto sin volver atrás --}}
            <div class="dropdown">
                <button class="btn admin-selector dropdown-toggle d-flex align-items-center gap-2"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-store" style="color: var(--pos-primary);"></i>
                    <span class="text-truncate" style="max-width: 11rem;">
                        {{ $negocioActivo?->nombre ?? 'Sin negocio' }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
                    @foreach($negociosDisponibles as $negocio)
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 {{ $negocio->id === $negocioActivo?->id ? 'active' : '' }}"
                               href="{{ route('negocio.activar', $negocio) }}">
                                @if($negocio->id === $negocioActivo?->id)
                                    <i class="fa-solid fa-check" style="color: var(--pos-success);"></i>
                                @else
                                    <i class="fa-regular fa-circle" style="color: var(--pos-text-muted);"></i>
                                @endif
                                {{ $negocio->nombre }}
                            </a>
                        </li>
                    @endforeach
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.mi-negocio') }}" wire:navigate>
                            <i class="fa-solid fa-sliders me-2"></i>Administrar negocios
                        </a>
                    </li>
                </ul>
            </div>

            <a href="{{ route('pos.main') }}" class="btn admin-btn-pos d-none d-md-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-cash-register"></i>
                <span>Abrir POS</span>
            </a>

            <div class="dropdown">
                <button class="btn admin-selector dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-circle-user" style="color: var(--pos-warning);"></i>
                    <span class="d-none d-lg-inline ms-1">{{ $usuario->nombre }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
                    <li><span class="dropdown-item-text small" style="color: var(--pos-text-muted);">
                        {{ \App\Enums\RolUsuario::tryFrom($usuario->rol)?->etiqueta() }}
                    </span></li>
                    <li><a class="dropdown-item" href="{{ route('rol.seleccionar') }}">
                        <i class="fa-solid fa-repeat me-2"></i>Cambiar de rol
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="color: var(--pos-danger);">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="admin-shell">
        {{-- ============ MENÚ LATERAL ============ --}}
        <nav class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar">
            <div class="admin-sidebar-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="admin-logo font-brand">🔥 DM</span>
                    <span class="text-truncate fw-semibold" style="max-width: 9rem;">
                        {{ $negocioActivo?->nombre ?? 'Sin negocio' }}
                    </span>
                </div>
                <button type="button" class="btn-close btn-close-white d-lg-none"
                        data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Cerrar"></button>
            </div>

            <ul class="admin-nav">
                @foreach($secciones as $seccion)
                    <li>
                        <a href="{{ route($seccion['ruta']) }}" wire:navigate
                           class="admin-nav-link {{ request()->routeIs($seccion['ruta']) ? 'active' : '' }}">
                            <i class="fa-solid {{ $seccion['icono'] }}"></i>
                            <span>{{ $seccion['texto'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- ============ CONTENIDO ============ --}}
        <main class="admin-content">
            @if(session('exito'))
                <div class="alert admin-alerta-exito d-flex align-items-center gap-2" role="alert">
                    <i class="fa-solid fa-circle-check"></i>{{ session('exito') }}
                </div>
            @endif
            @if(session('aviso'))
                <div class="alert admin-alerta-aviso d-flex align-items-center gap-2" role="alert">
                    <i class="fa-solid fa-circle-info"></i>{{ session('aviso') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
