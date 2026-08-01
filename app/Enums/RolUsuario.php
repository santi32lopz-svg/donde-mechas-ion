<?php

namespace App\Enums;

/**
 * Roles de la plataforma, en un único sitio.
 *
 * Se usa en dos niveles distintos y conviene no confundirlos:
 *
 *  - users.rol            → rol de plataforma. Solo Superadmin tiene alcance
 *                           global; para el resto es el rol por defecto que se
 *                           ofrece en la pantalla de selección.
 *  - negocio_usuario.rol  → rol efectivo del usuario EN ese negocio. Es el que
 *                           manda a la hora de decidir qué puede hacer, y
 *                           permite ser administrador de un negocio y cajero
 *                           de otro sin conflicto.
 *
 * Añadir un rol nuevo es añadir un caso aquí y una migración que amplíe la
 * restricción CHECK; el resto del código lo recoge solo.
 */
enum RolUsuario: string
{
    case Superadmin = 'superadmin';
    case Administrador = 'administrador';
    case Cajero = 'cajero';

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Roles asignables dentro de un negocio. Superadmin queda fuera a
     * propósito: es de plataforma, no de negocio.
     *
     * @return array<int, self>
     */
    public static function deNegocio(): array
    {
        return [self::Administrador, self::Cajero];
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadministrador',
            self::Administrador => 'Administrador',
            self::Cajero => 'Cajero',
        };
    }

    public function administraNegocio(): bool
    {
        return $this === self::Superadmin || $this === self::Administrador;
    }
}
