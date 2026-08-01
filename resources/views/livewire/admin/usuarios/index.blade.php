<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Usuarios</h1>
            <p class="admin-subtitulo">Quién puede entrar a este negocio y con qué rol.</p>
        </div>
        <button type="button" wire:click="abrirFormulario" class="btn btn-touch btn-touch-primary">
            <i class="fa-solid fa-user-plus"></i> Añadir usuario
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
                {{ $usuarioEnEdicion ? 'Cambiar rol' : 'Añadir usuario al negocio' }}
            </h2>

            <form wire:submit="guardar">
                @if($usuarioEnEdicion)
                    <p class="small mb-3" style="color: var(--pos-text-muted);">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Solo se cambia el rol dentro de este negocio. El nombre y la contraseña
                        pertenecen a la persona y pueden estar en uso en otros negocios.
                    </p>
                @endif

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="email">Correo</label>
                        <input id="email" type="email" wire:model="email"
                               class="form-control form-control-touch"
                               placeholder="persona@negocio.com"
                               @disabled($usuarioEnEdicion !== null)>
                        @error('email') <p class="admin-error">{{ $message }}</p> @enderror
                        @unless($usuarioEnEdicion)
                            <p class="small m-0 mt-1" style="color: var(--pos-text-muted);">
                                Si ya tiene cuenta en la plataforma, se vinculará sin crear una nueva.
                            </p>
                        @endunless
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label text-light fw-semibold" for="rol">Rol en este negocio</label>
                        <select id="rol" wire:model="rol" class="form-select form-select-touch">
                            @foreach($rolesDisponibles as $opcion)
                                <option value="{{ $opcion->value }}">{{ $opcion->etiqueta() }}</option>
                            @endforeach
                        </select>
                        @error('rol') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    @unless($usuarioEnEdicion)
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light fw-semibold" for="nombre">
                                Nombre <span class="fw-normal" style="color: var(--pos-text-muted);">(solo si es cuenta nueva)</span>
                            </label>
                            <input id="nombre" type="text" wire:model="nombre"
                                   class="form-control form-control-touch" placeholder="Nombre y apellido">
                            @error('nombre') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label text-light fw-semibold" for="password">
                                Contraseña <span class="fw-normal" style="color: var(--pos-text-muted);">(solo si es cuenta nueva)</span>
                            </label>
                            <input id="password" type="password" wire:model="password"
                                   class="form-control form-control-touch" placeholder="Mínimo 8 caracteres">
                            @error('password') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    @endunless
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
                   class="form-control form-control-touch" placeholder="Buscar por nombre o correo…">
        </div>
    </div>

    {{-- ============ LISTADO ============ --}}
    <div class="admin-panel p-0">
        <div class="table-responsive">
            <table class="table admin-tabla m-0 align-middle">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th style="width: 11rem;">Rol</th>
                        <th style="width: 9rem;">Acceso</th>
                        <th style="width: 12rem;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr wire:key="usuario-{{ $usuario->id }}">
                            <td>
                                <span class="fw-semibold text-white d-block">
                                    {{ $usuario->nombre }}
                                    @if($usuario->id === $usuarioActualId)
                                        <span class="badge admin-badge-etiqueta ms-1">Tú</span>
                                    @endif
                                </span>
                                <small style="color: var(--pos-text-muted);">{{ $usuario->email }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $usuario->pivot->rol === 'administrador' ? 'badge-status-completed' : 'admin-badge-etiqueta' }}">
                                    {{ \App\Enums\RolUsuario::tryFrom($usuario->pivot->rol)?->etiqueta() }}
                                </span>
                            </td>
                            <td>
                                <button type="button" wire:click="alternarAcceso({{ $usuario->id }})"
                                        class="btn btn-sm p-0 border-0 bg-transparent"
                                        title="{{ $usuario->pivot->activo ? 'Suspender acceso' : 'Restaurar acceso' }}">
                                    @if($usuario->pivot->activo)
                                        <span class="badge badge-status-completed">Activo</span>
                                    @else
                                        <span class="badge badge-status-danger">Suspendido</span>
                                    @endif
                                </button>
                            </td>
                            <td>
                                @if($usuarioADesvincular === $usuario->id)
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" wire:click="desvincular"
                                                class="btn btn-sm btn-touch btn-touch-danger">Confirmar</button>
                                        <button type="button" wire:click="cancelarDesvinculacion"
                                                class="btn btn-sm btn-touch admin-btn-secundario">No</button>
                                    </div>
                                @else
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" wire:click="abrirFormulario({{ $usuario->id }})"
                                                class="btn btn-sm admin-btn-icono" title="Cambiar rol">
                                            <i class="fa-solid fa-user-pen"></i>
                                        </button>
                                        <button type="button" wire:click="confirmarDesvinculacion({{ $usuario->id }})"
                                                class="btn btn-sm admin-btn-icono admin-btn-icono-peligro"
                                                title="Quitar del negocio">
                                            <i class="fa-solid fa-user-minus"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="fa-solid fa-users fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
                                <h3 class="text-white h5">Sin resultados</h3>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($usuarios->hasPages())
        <div class="mt-4">{{ $usuarios->links() }}</div>
    @endif
</div>
