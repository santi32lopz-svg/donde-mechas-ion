<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'POS SaaS')</title>

    {{-- Bootstrap, iconos y el tema Dark Touch van empaquetados por Vite,
         no por CDN: el POS debe abrir sin internet. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body style="min-height: 100vh;">

    <!-- Contenido Dinámico de Vistas Invitadas -->
    <main>
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>