<?php

namespace App\Livewire\Admin\Caja;

use App\Models\TurnoCaja;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Turnos de caja del negocio activo.
 *
 * De momento solo consulta. Abrir y cerrar turno, y registrar egresos, llegan
 * con el módulo de cobro: un turno sin ventas que cuadrar no sirve de nada.
 * El esquema ya soporta esas operaciones, así que no habrá que rediseñarlo.
 */
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.admin.caja.index', [
            'turnos' => TurnoCaja::query()
                ->with(['usuario:id,nombre'])
                ->withCount('pedidos')
                ->orderByDesc('fecha_apertura')
                ->paginate(15),
            'turnoAbierto' => TurnoCaja::query()
                ->where('estado', TurnoCaja::ABIERTO)
                ->latest('fecha_apertura')
                ->first(),
        ])->layout('components.layouts.admin');
    }
}
