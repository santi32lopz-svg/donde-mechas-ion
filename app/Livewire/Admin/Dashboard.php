<?php

namespace App\Livewire\Admin;

use App\Models\Producto;
use App\Support\TenantContext;
use Livewire\Component;

/**
 * Portada del panel de administración del negocio activo.
 *
 * De momento solo resume el catálogo. Las cifras de ventas y caja entrarán
 * cuando existan esos módulos; se deja el marco para no rehacerlo entonces.
 */
class Dashboard extends Component
{
    public function render()
    {
        // El scope global ya limita todo al negocio activo, así que estas
        // consultas no llevan where('negocio_id'): ese es justamente el punto.
        return view('livewire.admin.dashboard', [
            'negocio' => app(TenantContext::class)->negocio(),
            'totalProductos' => Producto::query()->count(),
            'productosNoDisponibles' => Producto::query()->where('disponible', false)->count(),
        ])->layout('components.layouts.admin');
    }
}
