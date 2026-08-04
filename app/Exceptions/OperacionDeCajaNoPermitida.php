<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La operación de caja se rechaza por una regla de negocio, no por un fallo.
 *
 * Igual que VentaNoRegistrable: quien está en barra necesita saber qué hacer,
 * no una pantalla de error.
 */
class OperacionDeCajaNoPermitida extends RuntimeException
{
    public static function turnoYaAbierto(): self
    {
        return new self(
            'Ya hay un turno de caja abierto para este negocio. '.
            'Hay un solo cajón, así que solo puede haber un turno a la vez.'
        );
    }

    public static function sinTurnoAbierto(): self
    {
        return new self('No hay ningún turno de caja abierto. Ábrelo antes de registrar movimientos.');
    }

    public static function baseNegativa(): self
    {
        return new self('La base de caja no puede ser negativa.');
    }

    public static function montoInvalido(): self
    {
        return new self('El monto debe ser mayor que cero.');
    }
}
