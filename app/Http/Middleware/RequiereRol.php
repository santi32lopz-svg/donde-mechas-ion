<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a determinados roles dentro del negocio activo.
 *
 * Hasta ahora las rutas administrativas solo comprobaban que hubiera un negocio
 * elegido, no quién era la persona. Un cajero que escribiera /admin/productos a
 * mano podía cambiar precios, y en /admin/usuarios darse a sí mismo el rol de
 * administrador. Mientras el POS fue la única pantalla tras el login, el hueco
 * quedaba tapado por la ausencia de enlaces; en cuanto aparece un botón para
 * volver al panel, deja de estarlo.
 *
 * La autorización se resuelve con el rol REAL del pivote, no con el que la
 * persona eligió en la pantalla de selección. Esa elección es un modo de
 * trabajo, no un permiso: quien administra el negocio y entró como cajero para
 * atender la barra no debería quedarse fuera de su propia administración.
 */
class RequiereRol
{
    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  string  ...$roles  Valores de RolUsuario admitidos en la ruta.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $negocioId = $this->tenant->negocioId();

        if ($usuario === null || $negocioId === null) {
            return redirect()->route('login');
        }

        $rol = $usuario->rolEn($negocioId);

        if ($rol !== null && in_array($rol->value, $roles, true)) {
            return $next($request);
        }

        // Un superadministrador alcanza cualquier módulo de cualquier negocio.
        if ($rol === RolUsuario::Superadmin) {
            return $next($request);
        }

        // Se redirige a donde la persona sí puede trabajar en lugar de a un 403
        // seco: quien está en barra necesita seguir vendiendo, no un error.
        return redirect()
            ->to($this->destinoPara($rol))
            ->with('aviso', 'No tienes permiso para entrar a esa sección.');
    }

    /**
     * Adónde mandar a quien no tiene permiso.
     *
     * De momento la terminal. Cuando exista el Panel Operativo del cajero,
     * este es el único punto que hay que cambiar.
     */
    private function destinoPara(?RolUsuario $rol): string
    {
        return route('pos.main');
    }
}
