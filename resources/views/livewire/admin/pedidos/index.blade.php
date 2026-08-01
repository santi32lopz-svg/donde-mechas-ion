<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Pedidos</h1>
            <p class="admin-subtitulo">
                Histórico de ventas. Numeración propia de {{ $negocio?->nombre }}:
                <code class="admin-slug">{{ $negocio?->prefijoDePedido() }}-000001</code>
            </p>
        </div>
        <select wire:model.live="estado" class="form-select form-select-touch" style="max-width: 14rem;">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendientes</option>
            <option value="completado">Completados</option>
            <option value="cancelado">Cancelados</option>
        </select>
    </div>

    <div class="admin-metricas mb-4">
        <div class="admin-metrica">
            <span class="admin-metrica-valor">{{ $pedidos->total() }}</span>
            <span class="admin-metrica-texto">Pedidos registrados</span>
        </div>
        <div class="admin-metrica">
            <span class="admin-metrica-valor">{{ $ultimoNumero }}</span>
            <span class="admin-metrica-texto">Último consecutivo entregado</span>
        </div>
    </div>

    <div class="admin-panel p-0">
        <div class="table-responsive">
            <table class="table admin-tabla m-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 10rem;">Número</th>
                        <th>Cajero</th>
                        <th style="width: 7rem;">Ítems</th>
                        <th style="width: 10rem;">Total</th>
                        <th style="width: 10rem;">Estado</th>
                        <th style="width: 12rem;">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedidos as $pedido)
                        <tr wire:key="pedido-{{ $pedido->id }}">
                            <td class="fw-bold text-white">{{ $pedido->numeroFormateado() }}</td>
                            <td>{{ $pedido->usuario?->nombre ?? '—' }}</td>
                            <td>{{ $pedido->detalles_count }}</td>
                            <td class="fw-bold" style="color: var(--pos-primary);">
                                ${{ number_format($pedido->monto_total, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($pedido->estado === 'completado')
                                    <span class="badge badge-status-completed">Completado</span>
                                @elseif($pedido->estado === 'cancelado')
                                    <span class="badge badge-status-danger">Cancelado</span>
                                @else
                                    <span class="badge badge-status-pending">Pendiente</span>
                                @endif
                            </td>
                            <td style="color: var(--pos-text-muted);">
                                {{ $pedido->created_at?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fa-solid fa-receipt fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
                                <h3 class="text-white h5">Todavía no hay pedidos</h3>
                                <p class="m-0" style="color: var(--pos-text-muted);">
                                    Aparecerán aquí en cuanto la terminal registre la primera venta.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($pedidos->hasPages())
        <div class="mt-4">{{ $pedidos->links() }}</div>
    @endif
</div>
