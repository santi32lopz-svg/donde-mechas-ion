<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Productos</h1>
            <p class="admin-subtitulo">Catálogo del negocio activo. Lo que crees aparece en el POS.</p>
        </div>
        <button type="button" wire:click="abrirFormulario" class="btn btn-touch btn-touch-primary">
            <i class="fa-solid fa-plus"></i> Nuevo producto
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
                {{ $productoEnEdicion ? 'Editar producto' : 'Nuevo producto' }}
            </h2>

            <form wire:submit="guardar">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="nombre">Nombre</label>
                        <input id="nombre" type="text" wire:model="nombre"
                               class="form-control form-control-touch" placeholder="Ej. Hamburguesa Especial">
                        @error('nombre') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label text-light fw-semibold" for="precio">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text admin-prefijo">$</span>
                            <input id="precio" type="number" step="1" min="0" wire:model="precio"
                                   class="form-control form-control-touch" placeholder="17000">
                        </div>
                        @error('precio') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label text-light fw-semibold" for="codigo">Código de barras</label>
                        <input id="codigo" type="text" wire:model="codigoBarras"
                               class="form-control form-control-touch" placeholder="Opcional">
                        @error('codigoBarras') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Etiqueta: si el negocio aún no tiene ninguna, se ofrece
                         crearla aquí mismo sin abandonar el formulario. --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="etiqueta">Etiqueta</label>

                        @if($etiquetas->isEmpty())
                            <div class="admin-sin-etiquetas">
                                <p class="m-0 mb-2">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    No hay etiquetas disponibles.
                                </p>
                                <button type="button" wire:click="abrirModalEtiqueta"
                                        class="btn btn-sm btn-touch btn-touch-primary">
                                    <i class="fa-solid fa-tag"></i> Crear etiqueta
                                </button>
                            </div>
                        @else
                            <div class="d-flex gap-2">
                                <select id="etiqueta" wire:model="etiquetaId" class="form-select form-select-touch">
                                    <option value="">Selecciona una etiqueta…</option>
                                    @foreach($etiquetas as $opcion)
                                        <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="abrirModalEtiqueta"
                                        class="btn admin-btn-icono" title="Crear etiqueta nueva">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        @endif
                        @error('etiquetaId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch admin-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="disponible" wire:model="disponible">
                            <label class="form-check-label text-light" for="disponible">
                                Disponible en el POS
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-light fw-semibold">Imagen</label>
                        <div class="admin-logo-pendiente">
                            <i class="fa-regular fa-image me-2"></i>
                            La carga de imágenes se habilitará más adelante.
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

    {{-- ============ BUSCADOR ============ --}}
    <div class="admin-panel mb-3 py-3">
        <div class="input-group">
            <span class="input-group-text admin-prefijo">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="busqueda"
                   class="form-control form-control-touch"
                   placeholder="Buscar por nombre o código de barras…">
        </div>
    </div>

    {{-- ============ LISTADO ============ --}}
    <div class="admin-panel p-0">
        <div class="table-responsive">
            <table class="table admin-tabla m-0 align-middle">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="width: 12rem;">Etiqueta</th>
                        <th style="width: 9rem;">Precio</th>
                        <th style="width: 9rem;">Estado</th>
                        <th style="width: 12rem;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr wire:key="producto-{{ $producto->id }}">
                            <td>
                                <span class="fw-semibold text-white d-block">{{ $producto->nombre }}</span>
                                @if($producto->codigo_barras)
                                    <small style="color: var(--pos-text-muted);">
                                        <i class="fa-solid fa-barcode me-1"></i>{{ $producto->codigo_barras }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge admin-badge-etiqueta">
                                    {{ $producto->etiqueta?->nombre ?? '—' }}
                                </span>
                            </td>
                            <td class="fw-bold" style="color: var(--pos-primary);">
                                ${{ number_format($producto->precio, 0, ',', '.') }}
                            </td>
                            <td>
                                <button type="button" wire:click="alternarDisponibilidad({{ $producto->id }})"
                                        class="btn btn-sm p-0 border-0 bg-transparent"
                                        title="{{ $producto->disponible ? 'Retirar del POS' : 'Mostrar en el POS' }}">
                                    @if($producto->disponible)
                                        <span class="badge badge-status-completed">Disponible</span>
                                    @else
                                        <span class="badge badge-status-pending">Oculto</span>
                                    @endif
                                </button>
                            </td>
                            <td>
                                @if($productoAEliminar === $producto->id)
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" wire:click="eliminar"
                                                class="btn btn-sm btn-touch btn-touch-danger">Confirmar</button>
                                        <button type="button" wire:click="cancelarEliminacion"
                                                class="btn btn-sm btn-touch admin-btn-secundario">No</button>
                                    </div>
                                @else
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" wire:click="abrirFormulario({{ $producto->id }})"
                                                class="btn btn-sm admin-btn-icono" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" wire:click="confirmarEliminacion({{ $producto->id }})"
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
                                <i class="fa-solid fa-burger fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
                                <h3 class="text-white h5">
                                    {{ $busqueda ? 'Sin resultados' : 'Todavía no hay productos' }}
                                </h3>
                                <p class="m-0" style="color: var(--pos-text-muted);">
                                    {{ $busqueda ? 'Prueba con otro término.' : 'Crea el primero con el botón de arriba.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($productos->hasPages())
        <div class="mt-4">{{ $productos->links() }}</div>
    @endif

    {{-- ============ DIÁLOGO RÁPIDO DE ETIQUETA ============
         Overlay propio en lugar del modal de Bootstrap: al gestionarlo
         Livewire no hay estado en el DOM que el morphing pueda perder. --}}
    @if($mostrandoModalEtiqueta)
        <div class="admin-modal-fondo" wire:key="modal-etiqueta">
            <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="tituloModalEtiqueta">
                <div class="admin-modal-cabecera">
                    <h2 class="admin-panel-titulo m-0" id="tituloModalEtiqueta">
                        <i class="fa-solid fa-tag" style="color: var(--pos-primary);"></i> Nueva etiqueta
                    </h2>
                    <button type="button" class="btn-close btn-close-white"
                            wire:click="cerrarModalEtiqueta" aria-label="Cerrar"></button>
                </div>

                <div class="admin-modal-cuerpo">
                    <p class="small mb-3" style="color: var(--pos-text-muted);">
                        Se seleccionará automáticamente y no perderás lo que ya escribiste.
                    </p>

                    <label class="form-label text-light fw-semibold" for="nuevaEtiqueta">Nombre</label>
                    <input id="nuevaEtiqueta" type="text" wire:model="nuevaEtiquetaNombre"
                           wire:keydown.enter.prevent="guardarEtiqueta"
                           class="form-control form-control-touch" placeholder="Ej. Bebidas" autofocus>
                    @error('nuevaEtiquetaNombre') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="admin-modal-pie">
                    <button type="button" wire:click="guardarEtiqueta"
                            class="btn btn-touch btn-touch-primary"
                            wire:loading.attr="disabled" wire:target="guardarEtiqueta">
                        <i class="fa-solid fa-check"></i> Crear y seleccionar
                    </button>
                    <button type="button" wire:click="cerrarModalEtiqueta"
                            class="btn btn-touch admin-btn-secundario">Cancelar</button>
                </div>
            </div>
        </div>
    @endif
</div>
