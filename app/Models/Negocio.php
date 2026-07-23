<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users() { return $this->hasMany(User::class, 'negocio_id'); }
    public function categorias() { return $this->hasMany(Categoria::class, 'negocio_id'); }
    public function productos() { return $this->hasMany(Producto::class, 'negocio_id'); }
    public function pedidos() { return $this->hasMany(Pedido::class, 'negocio_id'); }
    public function insumos() { return $this->hasMany(Insumo::class, 'negocio_id'); }
    public function turnoCajas() { return $this->hasMany(TurnoCaja::class, 'negocio_id'); }
    public function detallePedidos() { return $this->hasMany(DetallePedido::class, 'negocio_id'); }
    public function recetaProductos() { return $this->hasMany(RecetaProducto::class, 'negocio_id'); }
    public function gastoCajas() { return $this->hasMany(GastoCaja::class, 'negocio_id'); }
}
