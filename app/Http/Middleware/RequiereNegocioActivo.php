<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege las pantallas que solo tienen sentido con un negocio elegido.
 *
 * El caso típico es el superadministrador recién autenticado: alcanza todos los
 * negocios y no se le asigna ninguno, así que en lugar de mostrarle un panel
 * vacío se le manda a elegir. Vale igual para cualquiera cuyo negocio activo
 * haya dejado de estar disponible.
 */
class RequiereNegocioActivo
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenant->negocioId() === null) {
            return redirect()
                ->route('negocio.seleccionar')
                ->with('aviso', 'Selecciona el negocio sobre el que quieres trabajar.');
        }

        return $next($request);
    }
}
