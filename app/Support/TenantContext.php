<?php

namespace App\Support;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Fuente única del negocio activo (tenant) durante una petición.
 *
 * Hasta ahora el aislamiento multi-tenant preguntaba directamente por
 * auth()->user()->negocio_id, lo que ataba a cada usuario a un solo negocio.
 * La plataforma necesita que un administrador pueda moverse entre varios, así
 * que el negocio deja de ser un atributo del usuario y pasa a ser el contexto
 * de la petición: se elige, se guarda en sesión y se valida contra los
 * negocios a los que ese usuario tiene acceso.
 *
 * Todo lo demás —el scope global, el POS, los módulos administrativos— consulta
 * aquí y en ningún otro sitio. Ese es el punto: un único lugar donde equivocarse.
 */
class TenantContext
{
    public const CLAVE_SESION = 'negocio_activo_id';

    /** Negocio fijado explícitamente, por el middleware o por código. */
    private ?int $negocioId = null;

    private bool $fijado = false;

    /** Permite saltarse el aislamiento en bloques acotados y explícitos. */
    private bool $aislamientoSuspendido = false;

    /**
     * Fija el negocio activo para el resto de la petición.
     */
    public function usar(?int $negocioId): void
    {
        $this->negocioId = $negocioId;
        $this->fijado = true;
    }

    /**
     * Fija el negocio activo y lo recuerda en sesión, para que sobreviva a la
     * siguiente petición. Es lo que usa el selector de negocio.
     */
    public function cambiarA(int $negocioId): void
    {
        $this->usar($negocioId);
        session()->put(self::CLAVE_SESION, $negocioId);
    }

    public function olvidar(): void
    {
        $this->negocioId = null;
        $this->fijado = false;
        session()->forget(self::CLAVE_SESION);
    }

    /**
     * Negocio activo, o null si no se ha podido determinar.
     */
    public function negocioId(): ?int
    {
        if ($this->fijado) {
            return $this->negocioId;
        }

        return $this->resolverPorDefecto();
    }

    /**
     * Modelo del negocio activo, o null si no hay ninguno.
     */
    public function negocio(): ?Negocio
    {
        $id = $this->negocioId();

        return $id === null ? null : Negocio::query()->find($id);
    }

    /**
     * Indica si las consultas deben filtrarse por negocio.
     *
     * En consola, seeders, tests sin sesión y la propia pantalla de login no
     * hay tenant con el que filtrar, así que el aislamiento no aplica. Las
     * rutas de la aplicación están detrás del middleware 'auth', de modo que
     * un visitante anónimo nunca llega a consultar datos de negocio.
     */
    public function aplicaAislamiento(): bool
    {
        if ($this->aislamientoSuspendido) {
            return false;
        }

        return $this->fijado || Auth::hasUser() || Auth::check();
    }

    /**
     * Ejecuta un bloque sin aislamiento por negocio.
     *
     * Pensado para reportes de plataforma y tareas de mantenimiento. Es
     * deliberadamente explícito: saltarse el aislamiento debe verse en el
     * código y poder buscarse con un grep.
     */
    public function sinAislamiento(callable $callback): mixed
    {
        $previo = $this->aislamientoSuspendido;
        $this->aislamientoSuspendido = true;

        try {
            return $callback();
        } finally {
            $this->aislamientoSuspendido = $previo;
        }
    }

    /**
     * Comprueba si el usuario puede operar sobre un negocio.
     *
     * Se resuelve contra el pivote negocio_usuario, que es la única relación
     * entre usuarios y negocios desde la Etapa 2.
     */
    public function usuarioPuedeAcceder(?User $usuario, int $negocioId): bool
    {
        return $usuario?->puedeAccederA($negocioId) ?? false;
    }

    /**
     * Sin negocio fijado se intenta el de la sesión y, si no vale, el primero
     * disponible del usuario. La sesión se valida siempre: un id manipulado no
     * debe abrir el catálogo de otro negocio.
     */
    private function resolverPorDefecto(): ?int
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            return null;
        }

        $enSesion = session(self::CLAVE_SESION);

        if (is_int($enSesion) && $this->usuarioPuedeAcceder($usuario, $enSesion)) {
            return $enSesion;
        }

        // El superadministrador alcanza todos los negocios, así que elegir uno
        // por él sería arbitrario: debe seleccionarlo explícitamente.
        if ($usuario->esSuperadmin()) {
            return null;
        }

        return $usuario->negociosDisponibles()->value('negocios.id');
    }
}
