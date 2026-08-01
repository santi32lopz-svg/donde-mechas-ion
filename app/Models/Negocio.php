<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    use HasFactory;

    protected $guarded = [];

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
