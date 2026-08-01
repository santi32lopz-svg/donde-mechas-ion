<?php

namespace App\Livewire\Admin\Pedidos;

use App\Models\Pedido;
use App\Support\SecuenciaDePedidos;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Histórico de ventas del negocio activo.
 *
 * El listado ya es funcional aunque todavía no exista el cobro: en cuanto la
 * terminal empiece a registrar pedidos aparecerán aquí sin tocar este módulo.
 * Lo que falta por construir es la venta, no su consulta.
 */
class Index extends Component
{
    use WithPagination;

    public string $estado = '';

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $negocioId = app(TenantContext::class)->negocioId();

        return view('livewire.admin.pedidos.index', [
            'pedidos' => Pedido::query()
                ->with(['usuario:id,nombre'])
                ->withCount('detalles')
                ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
                ->orderByDesc('numero_secuencial')
                ->paginate(20),
            'negocio' => app(TenantContext::class)->negocio(),
            'ultimoNumero' => app(SecuenciaDePedidos::class)->ultimoEntregado($negocioId),
        ])->layout('components.layouts.admin');
    }
}
