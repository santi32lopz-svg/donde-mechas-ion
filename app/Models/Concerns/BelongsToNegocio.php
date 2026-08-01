<?php

namespace App\Models\Concerns;

use App\Models\Negocio;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla los registros por negocio en el SaaS multi-tenant.
 *
 * Antes cada consulta tenía que acordarse de añadir where('negocio_id', ...).
 * Basta con que un módulo nuevo lo olvide para filtrar datos de otro negocio,
 * que es justo lo que pasaba en la primera versión del POS. Con este trait el
 * filtro se aplica solo y el punto de control es uno.
 *
 * Reglas:
 *  - Sin sesión (consola, seeders, tests, pantalla de login) no se filtra nada,
 *    porque no hay negocio con el que filtrar.
 *  - Con sesión y negocio asignado se filtra por ese negocio.
 *  - Con sesión pero sin negocio asignado no se devuelve nada. Es preferible
 *    una pantalla vacía a mostrar el catálogo de todos los negocios.
 *
 * Para reportes que deban cruzar negocios existe scopeSinFiltroDeNegocio(),
 * que obliga a saltarse el aislamiento de forma explícita y rastreable.
 */
trait BelongsToNegocio
{
    public static function bootBelongsToNegocio(): void
    {
        static::addGlobalScope('negocio', function (Builder $query): void {
            $tenant = app(TenantContext::class);

            if (! $tenant->aplicaAislamiento()) {
                return;
            }

            $negocioId = $tenant->negocioId();
            $columna = $query->getModel()->getTable().'.negocio_id';

            if ($negocioId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where($columna, $negocioId);
        });

        static::creating(function (Model $model): void {
            if ($model->negocio_id === null) {
                $model->negocio_id = app(TenantContext::class)->negocioId();
            }
        });
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /**
     * Escapa del aislamiento por negocio. Usar solo en reportes de plataforma.
     */
    public function scopeSinFiltroDeNegocio(Builder $query): Builder
    {
        return $query->withoutGlobalScope('negocio');
    }
}
