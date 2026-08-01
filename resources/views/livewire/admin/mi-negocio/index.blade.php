<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Mi Negocio</h1>
            <p class="admin-subtitulo">Administra tus negocios y crea nuevos.</p>
        </div>
        <button type="button" wire:click="abrirFormulario" class="btn btn-touch btn-touch-primary">
            <i class="fa-solid fa-plus"></i> Crear nuevo negocio
        </button>
    </div>

    @if($mensajeExito)
        <div class="alert admin-alerta-exito d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-circle-check"></i>{{ $mensajeExito }}
        </div>
    @endif

    {{-- ============ FORMULARIO ============ --}}
    @if($mostrandoFormulario)
        <div class="admin-panel mb-4">
            <h2 class="admin-panel-titulo">
                {{ $negocioEnEdicion ? 'Editar negocio' : 'Nuevo negocio' }}
            </h2>

            <form wire:submit="guardar">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="nombre">Nombre del negocio</label>
                        <input id="nombre" type="text" wire:model="nombre"
                               class="form-control form-control-touch" placeholder="Ej. Pizza House">
                        @error('nombre') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="estado">Estado</label>
                        <select id="estado" wire:model="estado" class="form-select form-select-touch">
                            <option value="prueba">Prueba</option>
                            <option value="activo">Activo</option>
                            <option value="suspendido">Suspendido</option>
                        </select>
                        @error('estado') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label text-light fw-semibold" for="descripcion">Descripción</label>
                        <textarea id="descripcion" wire:model="descripcion" rows="2"
                                  class="form-control form-control-touch"
                                  placeholder="Comidas rápidas, gastrobar, pizzería..."></textarea>
                        @error('descripcion') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label text-light fw-semibold">Logo</label>
                        <div class="admin-logo-pendiente">
                            <i class="fa-regular fa-image me-2"></i>
                            La carga de logotipos se habilitará más adelante.
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
    <div class="admin-tarjetas">
        @forelse($negocios as $negocio)
            <div class="admin-tarjeta {{ $negocio->id === $negocioActivoId ? 'activa' : '' }}"
                 wire:key="negocio-{{ $negocio->id }}">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="admin-tarjeta-logo"><i class="fa-solid fa-store"></i></div>
                    @if($negocio->id === $negocioActivoId)
                        <span class="badge badge-status-completed">Activo ahora</span>
                    @endif
                </div>

                <h3 class="admin-tarjeta-titulo">{{ $negocio->nombre }}</h3>
                <p class="admin-tarjeta-texto">{{ $negocio->descripcion ?: 'Sin descripción' }}</p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge {{ $negocio->estado_suscripcion === 'activo' ? 'badge-status-completed' : 'badge-status-pending' }}">
                        {{ ucfirst($negocio->estado_suscripcion) }}
                    </span>
                    <span class="badge badge-status-pending">Plan {{ $negocio->plan }}</span>
                </div>

                <div class="admin-tarjeta-acciones">
                    <button type="button" wire:click="activar({{ $negocio->id }})"
                            class="btn btn-touch btn-touch-primary flex-grow-1">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Administrar
                    </button>
                    <button type="button" wire:click="abrirFormulario({{ $negocio->id }})"
                            class="btn btn-touch admin-btn-secundario" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="admin-panel text-center">
                <i class="fa-solid fa-store fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
                <h3 class="text-white">Todavía no hay negocios</h3>
                <p class="m-0" style="color: var(--pos-text-muted);">
                    Crea el primero con el botón de arriba.
                </p>
            </div>
        @endforelse
    </div>

    @if($negocios->hasPages())
        <div class="mt-4">{{ $negocios->links() }}</div>
    @endif
</div>
