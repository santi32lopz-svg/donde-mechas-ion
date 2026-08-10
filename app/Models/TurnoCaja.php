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
     * Efectivo que debería haber en el cajón.
     *
     * Solo cuentan las ventas en efectivo: una venta con tarjeta o transferencia
     * no mete un peso en el cajón, así que sumarla haría que el cierre nunca
     * cuadrara. Este método sumaba todas las ventas y era un error.
     */
    public function efectivoEsperado(): float
    {
        return (float) $this->monto_apertura
            + $this->ventasEnEfectivo()
            - $this->totalEgresos();
    }

    public function ventasEnEfectivo(): float
    {
        return (float) $this->pedidos()
            ->where('estado', '!=', 'cancelado')
            ->where('metodo_pago', 'efectivo')
            ->sum('monto_total');
    }

    /**
     * Todo lo vendido en el turno, cobrara como cobrara.
     */
    public function totalVendido(): float
    {
        return (float) $this->pedidos()
            ->where('estado', '!=', 'cancelado')
            ->sum('monto_total');
    }

    public function totalEgresos(): float
    {
        return (float) $this->gastos()->sum('monto');
    }

    /**
     * Ventas agrupadas por método de pago, para el resumen del cierre.
     *
     * @return array<string, float>
     */
    public function ventasPorMetodo(): array
    {
        return $this->pedidos()
            ->where('estado', '!=', 'cancelado')
            ->selectRaw('metodo_pago, SUM(monto_total) as total')
            ->groupBy('metodo_pago')
            ->pluck('total', 'metodo_pago')
            ->map(fn ($total) => (float) $total)
            ->all();
    }

    public function cantidadDePedidos(): int
    {
        return $this->pedidos()->where('estado', '!=', 'cancelado')->count();
    }
}
