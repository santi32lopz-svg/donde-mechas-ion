<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#121417">

    <title>{{ $title ?? 'POS SaaS' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">

    <main class="flex-grow-1 d-flex align-items-center justify-content-center p-4">
        <div class="pantalla-seleccion w-100">
            {{ $slot }}
        </div>
    </main>

    <footer class="text-center pb-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-decoration-none" style="color: var(--pos-text-muted);">
                <i class="fa-solid fa-right-from-bracket me-1"></i>Cerrar sesión
            </button>
        </form>
    </footer>

    @livewireScripts
</body>
</html>
