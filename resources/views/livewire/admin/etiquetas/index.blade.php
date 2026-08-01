<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Etiquetas</h1>
            <p class="admin-subtitulo">Agrupan los productos y son los botones de filtro del POS.</p>
        </div>
        <button type="button" wire:click="abrirFormulario" class="btn btn-touch btn-touch-primary">
            <i class="fa-solid fa-plus"></i> Nueva etiqueta
        </button>
    </div>

    @if($mensajeExito)
        <div class="alert admin-alerta-exito d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-circle-check"></i>{{ $mensajeExito }}
        </div>
    @endif

    @if($mensajeError)
        <div class="alert admin-alerta-error d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>{{ $mensajeError }}
        </div>
    @endif

    {{-- ============ FORMULARIO ============ --}}
    @if($mostrandoFormulario)
        <div class="admin-panel mb-4">
            <h2 class="admin-panel-titulo">
                {{ $etiquetaEnEdicion ? 'Editar etiqueta' : 'Nueva etiqueta' }}
            </h2>

            <form wire:submit="guardar">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="nombre">Nombre</label>
                        <input id="nombre" type="text" wire:model="nombre"
                               class="form-control form-control-touch" placeholder="Ej. Hamburguesas">
                        @error('nombre') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label text-light fw-semibold" for="orden">Orden en el POS</label>
                        <input id="orden" type="number" min="0" wire:model="ordenVisualizacion"
                               class="form-control form-control-touch">
                        @error('ordenVisualizacion') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch admin-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="activo" wire:model="activo">
                            <label class="form-check-label text-light" for="activo">Visible en el POS</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-touch btn-touch-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                    <button type="button" wire:click="cancelar" class="btn btn-touch admin-btn-secundario">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============ LISTADO ============ --}}
    <div class="admin-panel p-0">
        <div class="table-responsive">
            <table class="table admin-tabla m-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 5rem;">Orden</th>
                        <th>Nombre</th>
                        <th style="width: 9rem;">Productos</th>
                        <th style="width: 8rem;">Estado</th>
                        <th style="width: 11rem;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($etiquetas as $etiqueta)
                        <tr wire:key="etiqueta-{{ $etiqueta->id }}">
                            <td><span class="admin-orden">{{ $etiqueta->orden_visualizacion }}</span></td>
                            <td class="fw-semibold text-white">{{ $etiqueta->nombre }}</td>
                            <td>
                                <span style="color: var(--pos-text-muted);">
                                    {{ $etiqueta->productos_count }}
                                </span>
                            </td>
                            <td>
                                @if($etiqueta->activo)
                                    <span class="badge badge-status-completed">Visible</span>
                                @else
                                    <span class="badge badge-status-pending">Oculta</span>
                                @endif
                            </td>
                            <td>
                                @if($etiquetaAEliminar === $etiqueta->id)
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" wire:click="eliminar"
                                                class="btn btn-sm btn-touch btn-touch-danger">
                                            Confirmar
                                        </button>
                                        <button type="button" wire:click="cancelarEliminacion"
                                                class="btn btn-sm btn-touch admin-btn-secundario">
                                            No
                                        </button>
                                    </div>
                                @else
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" wire:click="abrirFormulario({{ $etiqueta->id }})"
                                                class="btn btn-sm admin-btn-icono" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" wire:click="confirmarEliminacion({{ $etiqueta->id }})"
                                                class="btn btn-sm admin-btn-icono admin-btn-icono-peligro"
                                                title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fa-solid fa-tags fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
                                <h3 class="text-white h5">Todavía no hay etiquetas</h3>
                                <p class="m-0" style="color: var(--pos-text-muted);">
                                    Crea la primera para empezar a agrupar productos en el POS.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($etiquetas->hasPages())
        <div class="mt-4">{{ $etiquetas->links() }}</div>
    @endif
</div>
