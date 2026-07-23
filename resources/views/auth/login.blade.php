@extends('layouts.guest')

@section('title', 'Iniciar Sesión - POS SaaS')

@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        
        <!-- Branding / Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; background-color: var(--pos-primary, #ff3838);">
                <i class="bi bi-shop fs-2 text-white"></i>
            </div>
            <h2 class="fw-bold text-white mb-1">Punto de Venta</h2>
            <p style="color: #a0a0a0;">Ingresa tus credenciales para acceder al sistema</p>
        </div>

        <!-- Tarjeta del Formulario -->
        <div class="card border-0 shadow-lg" style="background-color: var(--pos-bg-card, #1e1e1e); border-radius: 12px; border: 1px solid var(--pos-border-color, #333);">
            <div class="card-body p-4">
                
                <!-- AQUÍ VA EL FRAGMENTO DEL CÓDIGO DE ERRORES -->
                @if ($errors->any())
                    <div class="alert alert-danger border border-danger mb-4 text-white" style="background-color: #721c24; border-radius: 12px;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                            <strong class="text-white">Error de acceso</strong>
                        </div>
                        <ul class="mb-0 ps-3 small text-white-50">
                            @foreach ($errors->all() as $error)
                                <li class="text-white">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <!-- FIN DEL FRAGMENTO -->

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <!-- Correo Electrónico -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-white">Correo Electrónico</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               class="form-control form-control-touch" 
                               placeholder="usuario@negocio.com" 
                               value="{{ old('email') }}" 
                               required 
                               autofocus>
                    </div>

                    <!-- Contraseña -->
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold text-white">Contraseña</label>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control form-control-touch" 
                               placeholder="••••••••" 
                               required>
                    </div>

                    <!-- Recordar sesión -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" style="width: 20px; height: 20px;">
                        <label class="form-check-label ms-2 pt-1 text-white-50" for="remember">
                            Recordar este dispositivo
                        </label>
                    </div>

                    <!-- Botón de Ingreso Táctil -->
                    <button type="submit" 
                            class="btn btn-touch-primary w-100 py-3 shadow-lg fw-bold d-flex align-items-center justify-content-center gap-2"
                            style="background: linear-gradient(135deg, #ff3838 0%, #d63031 100%); 
                                color: #ffffff; 
                                border: none; 
                                border-radius: 12px; 
                                font-size: 1.2rem; 
                                box-shadow: 0 4px 15px rgba(255, 56, 56, 0.4);">
                        <i class="bi bi-box-arrow-in-right fs-4"></i>
                        <span>Iniciar Sesión</span>
                    </button>
                </form>

            </div>
        </div>

        <div class="text-center mt-4">
            <small style="color: #6c757d;">POS System SaaS Multi-Tenant &copy; {{ date('Y') }}</small>
        </div>

    </div>
</div>
@endsection