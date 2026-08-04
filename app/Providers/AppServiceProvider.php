<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /** Cinco intentos por minuto y correo: suficiente para un error de tecleo. */
    private const INTENTOS_DE_LOGIN_POR_MINUTO = 5;

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
        $this->limitarIntentosDeLogin();
    }

    /**
     * El login no tenía ningún freno: se podían probar contraseñas
     * indefinidamente contra un sistema que gestiona el dinero del negocio.
     *
     * La clave combina correo e IP para que atacar una cuenta concreta no
     * bloquee de paso a todo el local, que suele salir por una única IP.
     */
    private function limitarIntentosDeLogin(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $clave = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(self::INTENTOS_DE_LOGIN_POR_MINUTO)
                ->by($clave)
                ->response(fn (Request $peticion, array $cabeceras) => back()
                    ->withInput($peticion->only('email'))
                    ->withErrors([
                        'email' => 'Demasiados intentos fallidos. Espera un minuto antes de volver a intentarlo.',
                    ]));
        });
    }
}
