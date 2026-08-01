<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de una venta.
 *
 * Guarda una copia del precio y del nombre del producto en el momento de
 * venderlo. Sin esa copia, renombrar o cambiar el precio de un producto
 * reescribiría el histórico en silencio.
 */
class DetallePedido extends Model
{
    use BelongsToNegocio;

    protected $fillable = [
        'negocio_id',
        'pedido_id',
        'producto_id',
        'nombre_producto',
        'vendido_sin_disponibilidad',
        'precio_unitario',
        'cantidad',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'cantidad' => 'integer',
            'vendido_sin_disponibilidad' => 'boolean',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
