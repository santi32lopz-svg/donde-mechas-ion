<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\RequiereNegocioActivo;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Etiquetas;
use App\Livewire\Admin\MiNegocio;
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
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
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
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', Dashboard::class)->name('dashboard');
            Route::get('/mi-negocio', MiNegocio\Index::class)->name('mi-negocio');
            Route::get('/etiquetas', Etiquetas\Index::class)->name('etiquetas');

            // Módulos con la estructura reservada; se implementan por etapas.
            $pendientes = [
                'productos' => ['Productos', 'Catálogo del negocio activo.', 'fa-burger'],
                'pedidos' => ['Pedidos', 'Historial de ventas del negocio.', 'fa-receipt'],
                'caja' => ['Caja', 'Turnos, egresos y cuadre de caja.', 'fa-cash-register'],
                'usuarios' => ['Usuarios', 'Quién puede entrar y con qué rol.', 'fa-users'],
                'configuracion' => ['Configuración', 'Preferencias del negocio.', 'fa-gear'],
            ];

            foreach ($pendientes as $ruta => [$titulo, $descripcion, $icono]) {
                Route::get("/{$ruta}", fn () => view('admin.proximamente', compact('titulo', 'descripcion', 'icono')))
                    ->name($ruta);
            }
        });
    });
});
