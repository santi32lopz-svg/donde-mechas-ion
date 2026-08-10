<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Caja</h1>
            <p class="admin-subtitulo">Turnos, egresos y cuadre del negocio activo.</p>
        </div>
    </div>

    @if($turnoAbierto)
        <div class="admin-panel mb-4">
            <h2 class="admin-panel-titulo">
                <i class="fa-solid fa-lock-open" style="color: var(--pos-success);"></i> Turno abierto
            </h2>
            <div class="admin-metricas">
                <div class="admin-metrica">
                    <span class="admin-metrica-valor">${{ number_format($turnoAbierto->monto_apertura, 0, ',', '.') }}</span>
                    <span class="admin-metrica-texto">Base de apertura</span>
                </div>
                <div class="admin-metrica">
                    <span class="admin-metrica-valor">${{ number_format($turnoAbierto->efectivoEsperado(), 0, ',', '.') }}</span>
                    <span class="admin-metrica-texto">Saldo esperado en cajón</span>
                </div>
                <div class="admin-metrica">
                    <span class="admin-metrica-valor">{{ $turnoAbierto->fecha_apertura?->format('H:i') }}</span>
                    <span class="admin-metrica-texto">Abierto desde</span>
                </div>
            </div>
        </div>
    @else
        <div class="admin-panel mb-4 text-center">
            <i class="fa-solid fa-cash-register fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
            <h2 class="admin-panel-titulo justify-content-center">No hay ningún turno abierto</h2>
            <p class="m-0" style="color: var(--pos-text-muted);">
                Abrir y cerrar turnos, y registrar egresos, llegan con el módulo de cobro.
                Un turno sin ventas que cuadrar no aporta nada todavía.
            </p>
        </div>
    @endif

    <div class="admin-panel p-0">
        <div class="table-responsive">
            <table class="table admin-tabla m-0 align-middle">
                <thead>
                    <tr>
                        <th>Responsable</th>
                        <th style="width: 10rem;">Apertura</th>
                        <th style="width: 10rem;">Cierre</th>
                        <th style="width: 7rem;">Ventas</th>
                        <th style="width: 9rem;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($turnos as $turno)
                        <tr wire:key="turno-{{ $turno->id }}">
                            <td class="fw-semibold text-white">{{ $turno->usuario?->nombre ?? '—' }}</td>
                            <td>${{ number_format($turno->monto_apertura, 0, ',', '.') }}</td>
                            <td>
                                @if($turno->monto_cierre !== null)
                                    ${{ number_format($turno->monto_cierre, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $turno->pedidos_count }}</td>
                            <td>
                                @if($turno->estado === 'abierto')
                                    <span class="badge badge-status-completed">Abierto</span>
                                @else
                                    <span class="badge admin-badge-etiqueta">Cerrado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <p class="m-0" style="color: var(--pos-text-muted);">
                                    Todavía no se ha abierto ningún turno.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($turnos->hasPages())
        <div class="mt-4">{{ $turnos->links() }}</div>
    @endif
</div>
