<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de caja: desde que se abre con una base hasta que se cuadra y cierra.
 */
class TurnoCaja extends Model
{
    use BelongsToNegocio;

    public const ABIERTO = 'abierto';

    public const CERRADO = 'cerrado';

    protected $fillable = [
        'negocio_id',
        'user_id',
        'monto_apertura',
        'monto_cierre',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'observaciones_apertura',
    ];

    protected function casts(): array
    {
        return [
            'monto_apertura' => 'decimal:2',
            'monto_cierre' => 'decimal:2',
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(GastoCaja::class);
    }

    /**
     * Efectivo que debería haber en el cajón: la base, más lo vendido, menos
     * los egresos registrados.
     */
    public function saldoEsperado(): float
    {
        return (float) $this->monto_apertura
            + (float) $this->pedidos()->sum('monto_total')
            - (float) $this->gastos()->sum('monto');
    }
}
