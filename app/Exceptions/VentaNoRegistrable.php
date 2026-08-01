<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La venta no se puede registrar por una regla de negocio, no por un fallo.
 *
 * Se distingue de una excepción cualquiera para que la terminal pueda mostrar
 * el motivo al cajero en lugar de una pantalla de error: quien está cobrando
 * necesita saber qué hacer, no un stack trace.
 */
class VentaNoRegistrable extends RuntimeException
{
    public static function carritoVacio(): self
    {
        return new self('No hay nada que cobrar.');
    }

    public static function sinTurnoDeCaja(): self
    {
        return new self(
            'Para cobrar en efectivo debe haber un turno de caja abierto. '.
            'Ábrelo desde el módulo de Caja antes de continuar.'
        );
    }

    public static function metodoDePagoInvalido(string $metodo): self
    {
        return new self("El método de pago «{$metodo}» no está permitido.");
    }
}
