<?php

use App\Enums\RolUsuario;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\RequiereNegocioActivo;
use App\Http\Middleware\RequiereRol;
use App\Livewire\Admin\Caja;
use App\Livewire\Admin\Configuracion;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Etiquetas;
use App\Livewire\Admin\Pedidos;
use App\Livewire\Admin\MiNegocio;
use App\Livewire\Admin\Productos;
use App\Livewire\Admin\Usuarios;
use App\Livewire\Auth\SeleccionNegocio;
use App\Livewire\Auth\SeleccionRol;
use App\Livewire\Pos\PosMain;
use App\Models\Negocio;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;

// Redirección inicial
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    // Sin este freno se podían probar contraseñas indefinidamente contra un
    // sistema que gestiona el dinero del negocio.
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');
});

// Rutas Protegidas (Requieren Login)
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // --- Elección de contexto: con qué rol y sobre qué negocio se trabaja ---
    Route::get('/seleccionar-rol', SeleccionRol::class)->name('rol.seleccionar');
    Route::get('/seleccionar-negocio', SeleccionNegocio::class)->name('negocio.seleccionar');

    // Cambio rápido de negocio desde el selector de la barra superior.
    Route::get('/negocio/{negocio}/activar', function (Negocio $negocio) {
        abort_unless(auth()->user()->puedeAccederA($negocio->id), 403);

        app(TenantContext::class)->cambiarA($negocio->id);
        session()->put('tenant_nombre', $negocio->nombre);

        return back()->with('exito', "Ahora trabajas sobre «{$negocio->nombre}».");
    })->name('negocio.activar');

    // --- Zona que exige un negocio activo ---
    Route::middleware(RequiereNegocioActivo::class)->group(function () {

        // Terminal de venta. El componente es el mismo para todos los negocios:
        // consume el que esté activo en la sesión.
        Route::get('/pos', PosMain::class)->name('pos.main');

        // --- Panel de administración ---
        // Reservado a quien administra el negocio. Sin esta restricción un
        // cajero podía escribir la URL y cambiar precios o darse permisos.
        Route::middleware(RequiereRol::class.':'.RolUsuario::Administrador->value)
            ->prefix('admin')->name('admin.')->group(function () {
            Route::get('/', Dashboard::class)->name('dashboard');
            Route::get('/mi-negocio', MiNegocio\Index::class)->name('mi-negocio');
            Route::get('/etiquetas', Etiquetas\Index::class)->name('etiquetas');
            Route::get('/productos', Productos\Index::class)->name('productos');
            Route::get('/usuarios', Usuarios\Index::class)->name('usuarios');
            Route::get('/configuracion', Configuracion::class)->name('configuracion');
            Route::get('/pedidos', Pedidos\Index::class)->name('pedidos');
            Route::get('/caja', Caja\Index::class)->name('caja');
        });
    });
});
