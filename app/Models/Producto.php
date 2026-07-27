<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $guarded = [];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
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
}
