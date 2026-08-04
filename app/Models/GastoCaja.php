<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salida de dinero del cajón durante un turno: pago al domiciliario, compra
 * urgente de insumos. Sin registrarlas el cuadre de caja nunca da.
 */
class GastoCaja extends Model
{
    use BelongsToNegocio;

    protected $fillable = [
        'negocio_id',
        'turno_caja_id',
        'user_id',
        'tipo',
        'descripcion',
        'monto',
    ];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2'];
    }

    public function turnoCaja(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class);
    }
}
