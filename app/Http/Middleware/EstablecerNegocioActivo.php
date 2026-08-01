<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el negocio activo al inicio de cada petición autenticada.
 *
 * Deja el tenant fijado en TenantContext antes de que se ejecute ningún
 * componente, de modo que el scope global no tenga que resolverlo una y otra
 * vez por consulta. Si el id guardado en sesión ya no es accesible para el
 * usuario —le revocaron el acceso, o alguien lo manipuló— se descarta en
 * silencio y se cae al negocio por defecto.
 */
class EstablecerNegocioActivo
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            return $next($request);
        }

        $enSesion = session(TenantContext::CLAVE_SESION);

        if (is_int($enSesion) && $this->tenant->usuarioPuedeAcceder($usuario, $enSesion)) {
            $this->tenant->usar($enSesion);

            return $next($request);
        }

        // La sesión traía un negocio inservible: se limpia para no reintentarlo
        // en cada petición.
        if ($enSesion !== null) {
            session()->forget(TenantContext::CLAVE_SESION);
        }

        // Al superadministrador no se le asigna ninguno: los alcanza todos y
        // elegir por él sería arbitrario.
        $this->tenant->usar(
            $usuario->esSuperadmin()
                ? null
                : $usuario->negociosDisponibles()->value('negocios.id')
        );

        return $next($request);
    }
}
