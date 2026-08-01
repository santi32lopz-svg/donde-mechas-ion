<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Configuración</h1>
            <p class="admin-subtitulo">Datos operativos de {{ $negocio?->nombre }}.</p>
        </div>
    </div>

    @if($mensajeExito)
        <div class="alert admin-alerta-exito d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-circle-check"></i>{{ $mensajeExito }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="admin-panel">
                <h2 class="admin-panel-titulo">
                    <i class="fa-solid fa-store" style="color: var(--pos-primary);"></i> Identidad del negocio
                </h2>

                <form wire:submit="guardar">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-light fw-semibold" for="nombre">Nombre</label>
                            <input id="nombre" type="text" wire:model="nombre"
                                   class="form-control form-control-touch">
                            @error('nombre') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label text-light fw-semibold" for="descripcion">Descripción</label>
                            <textarea id="descripcion" wire:model="descripcion" rows="2"
                                      class="form-control form-control-touch"></textarea>
                            @error('descripcion') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label text-light fw-semibold" for="nit">NIT / RUT</label>
                            <input id="nit" type="text" wire:model="nitRut"
                                   class="form-control form-control-touch">
                            @error('nitRut') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label text-light fw-semibold" for="telefono">Teléfono</label>
                            <input id="telefono" type="text" wire:model="telefono"
                                   class="form-control form-control-touch">
                            @error('telefono') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label text-light fw-semibold" for="direccion">Dirección</label>
                            <textarea id="direccion" wire:model="direccion" rows="2"
                                      class="form-control form-control-touch"></textarea>
                            @error('direccion') <p class="admin-error">{{ $message }}</p> @enderror
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
                            <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="admin-panel">
                <h2 class="admin-panel-titulo">
                    <i class="fa-solid fa-receipt" style="color: var(--pos-warning);"></i> Suscripción
                </h2>

                <dl class="admin-datos m-0">
                    <dt>Plan</dt>
                    <dd>{{ ucfirst($negocio?->plan ?? '—') }}</dd>

                    <dt>Estado</dt>
                    <dd>
                        <span class="badge {{ $negocio?->estado_suscripcion === 'activo' ? 'badge-status-completed' : 'badge-status-pending' }}">
                            {{ ucfirst($negocio?->estado_suscripcion ?? '—') }}
                        </span>
                    </dd>

                    <dt>Vencimiento</dt>
                    <dd>{{ $negocio?->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha' }}</dd>

                    <dt>Identificador</dt>
                    <dd><code class="admin-slug">{{ $negocio?->slug }}</code></dd>
                </dl>

                <p class="small m-0 mt-3" style="color: var(--pos-text-muted);">
                    El plan y el estado se gestionan desde
                    <a href="{{ route('admin.mi-negocio') }}" wire:navigate style="color: var(--pos-primary);">Mi Negocio</a>.
                    El identificador no cambia al renombrar el negocio, para no romper enlaces ya compartidos.
                </p>
            </div>
        </div>
    </div>
</div>
