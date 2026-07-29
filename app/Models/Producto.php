<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use BelongsToNegocio;

    /**
     * Lista explícita en lugar de $guarded = [], que dejaba asignable cualquier
     * columna. negocio_id sigue aquí porque los seeders lo pasan directo; en
     * peticiones autenticadas lo rellena el trait BelongsToNegocio.
     */
    protected $fillable = [
        'negocio_id',
        'categoria_id',
        'nombre',
        'codigo_barras',
        'precio',
        'disponible',
        'imagen_path',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
        ];
    }

    /**
     * Filtra por nombre o código de barras para el buscador del POS.
     *
     * En PostgreSQL LIKE distingue mayúsculas, así que buscar "hamb" no
     * encuentra "Hamburguesa". ILIKE es su equivalente insensible; MySQL y
     * SQLite ya lo son con LIKE y no conocen ILIKE, de ahí que el operador
     * se elija según el driver de la conexión.
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return $query;
        }

        $operador = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // Se escapan los comodines para que escribir "%" o "_" no liste todo el catálogo.
        $patron = '%'.addcslashes($termino, '%_\\').'%';

        return $query->where(function (Builder $sub) use ($operador, $patron) {
            $sub->where('nombre', $operador, $patron)
                ->orWhere('codigo_barras', $operador, $patron);
        });
    }

    /**
     * Coincidencia exacta de código de barras, para el lector del POS.
     * Aprovecha el índice, a diferencia del LIKE con comodín inicial.
     */
    public function scopePorCodigoDeBarras(Builder $query, ?string $codigo): Builder
    {
        return $query->where('codigo_barras', trim((string) $codigo));
    }
}
