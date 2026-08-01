<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agrupa los productos de un negocio y alimenta los botones de filtro del POS.
 *
 * Se llamaba Categoria hasta la Etapa 4; la plataforma usa "etiqueta" en toda
 * su terminología para no obligar a nadie a aprender dos nombres del mismo
 * concepto. La relación con Producto es 1:N a propósito: un producto pertenece
 * a una sola etiqueta, que es lo que espera la cuadrícula del POS.
 */
class Etiqueta extends Model
{
    use BelongsToNegocio;

    protected $fillable = [
        'negocio_id',
        'nombre',
        'orden_visualizacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden_visualizacion' => 'integer',
        ];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Una etiqueta con productos no puede borrarse: la clave foránea es
     * RESTRICT y la base de datos rechazaría el DELETE.
     */
    public function sePuedeEliminar(): bool
    {
        return ! $this->productos()->exists();
    }
}
