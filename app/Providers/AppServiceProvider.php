<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton a propósito: el negocio activo es estado de la petición.
        // Si se resolviera una instancia nueva en cada llamada, lo que fija el
        // middleware se perdería y el scope global volvería a adivinar.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
