<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    use HasFactory;

    /** Prefijo de los pedidos cuando el negocio no ha configurado el suyo. */
    public const PREFIJO_PEDIDO_POR_DEFECTO = 'PED';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fecha_vencimiento' => 'datetime'];
    }

    /**
     * Prefijo que antecede al consecutivo en la tirilla: DM-000123.
     *
     * Solo afecta a la presentación, así que cambiarlo no toca ningún pedido
     * guardado. La contrapartida es que los pedidos antiguos pasan a mostrarse
     * con el prefijo nuevo.
     */
    public function prefijoDePedido(): string
    {
        return trim((string) $this->prefijo_pedido) ?: self::PREFIJO_PEDIDO_POR_DEFECTO;
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'negocio_usuario')
            ->withPivot(['rol', 'activo'])
            ->withTimestamps();
    }

    public function etiquetas() { return $this->hasMany(Etiqueta::class, 'negocio_id'); }
    public function productos() { return $this->hasMany(Producto::class, 'negocio_id'); }
    public function pedidos() { return $this->hasMany(Pedido::class, 'negocio_id'); }
    public function insumos() { return $this->hasMany(Insumo::class, 'negocio_id'); }
    public function turnoCajas() { return $this->hasMany(TurnoCaja::class, 'negocio_id'); }
    public function detallePedidos() { return $this->hasMany(DetallePedido::class, 'negocio_id'); }
    public function recetaProductos() { return $this->hasMany(RecetaProducto::class, 'negocio_id'); }
    public function gastoCajas() { return $this->hasMany(GastoCaja::class, 'negocio_id'); }
}
