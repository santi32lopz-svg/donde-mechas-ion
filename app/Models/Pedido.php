<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta registrada en la terminal.
 *
 * El consecutivo se guarda como entero (numero_secuencial) y es único dentro
 * del negocio. El prefijo vive en el negocio y solo interviene al presentarlo,
 * de modo que cambiarlo no obliga a tocar ninguna fila.
 */
class Pedido extends Model
{
    use BelongsToNegocio;

    protected $fillable = [
        'negocio_id',
        'numero_secuencial',
        'user_id',
        'turno_caja_id',
        'estado',
        'metodo_pago',
        'monto_total',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'numero_secuencial' => 'integer',
            'monto_total' => 'decimal:2',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function turnoCaja(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class);
    }

    /**
     * Número tal como lo ve el cliente: DM-000123.
     */
    public function numeroFormateado(): string
    {
        $prefijo = $this->negocio?->prefijoDePedido() ?? Negocio::PREFIJO_PEDIDO_POR_DEFECTO;

        return $prefijo.'-'.str_pad((string) $this->numero_secuencial, 6, '0', STR_PAD_LEFT);
    }
}
