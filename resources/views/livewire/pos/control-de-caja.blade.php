<div class="d-flex align-items-center gap-2">

    {{-- ============ FRANJA DE ESTADO ============
         Solo estado operativo. Las cifras financieras viven en el cierre,
         el Dashboard y el Panel Operativo. --}}
    @if($turno)
        {{-- Lo que importa mientras se vende es cuánto dinero hay en el cajón,
             no con cuánto se abrió. La base se ve en el modal de cierre. --}}
        <div class="pos-caja-estado d-none d-md-flex align-items-center gap-2">
            <span class="pos-caja-punto"></span>
            <div class="lh-1">
                <span class="d-block" style="font-size: 0.65rem; color: var(--pos-text-muted);">EFECTIVO EN CAJA</span>
                <span class="fw-bold {{ $efectivoEsperado < 0 ? 'text-danger' : '' }}"
                      style="{{ $efectivoEsperado < 0 ? '' : 'color: var(--pos-success);' }}">
                    ${{ number_format($efectivoEsperado, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <button type="button" wire:click="abrirFormularioDeEgreso"
                class="btn btn-touch btn-touch-danger px-3">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <span class="d-none d-lg-inline">Salida Caja</span>
        </button>
    @else
        {{-- La interfaz dice qué hacer, no solo qué falta. --}}
        <button type="button" wire:click="abrirFormularioDeApertura"
                class="btn btn-touch pos-btn-abrir-caja px-3">
            <i class="fa-solid fa-lock-open"></i>
            <span>Abrir Caja</span>
        </button>
    @endif

    {{-- Salida del POS. Sin esto la terminal es un callejón: la única forma de
         salir era cerrar sesión. --}}
    @if($puedeVolverAlPanel)
        <a href="{{ route('admin.dashboard') }}" wire:navigate
           class="btn btn-touch admin-btn-secundario px-3">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="d-none d-lg-inline">Volver al Panel</span>
        </a>
    @endif

    {{-- ============ AVISOS ============
         Se ocultan solos a los pocos segundos y se colocan bajo la cabecera
         centrados, para no taparle la tirilla al cajero. --}}
    @if($mensajeExito || $mensajeError)
        <div class="pos-caja-aviso {{ $mensajeError ? 'error' : '' }}"
             wire:key="aviso-caja-{{ md5($mensajeError ?? $mensajeExito) }}"
             x-data="{ visible: true }"
             x-init="setTimeout(() => visible = false, 5000)"
             x-show="visible"
             x-transition.opacity>
            <i class="fa-solid {{ $mensajeError ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
            <span>{{ $mensajeError ?? $mensajeExito }}</span>
        </div>
    @endif

    {{-- ============ DIÁLOGO: ABRIR CAJA ============ --}}
    @if($abriendoCaja)
        <div class="admin-modal-fondo" wire:key="dialogo-apertura">
            <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="tituloApertura">
                <div class="admin-modal-cabecera">
                    <h2 class="admin-panel-titulo m-0" id="tituloApertura">
                        <i class="fa-solid fa-lock-open" style="color: var(--pos-success);"></i> Abrir caja
                    </h2>
                    <button type="button" class="btn-close btn-close-white"
                            wire:click="cancelarApertura" aria-label="Cancelar"></button>
                </div>

                <div class="admin-modal-cuerpo">
                    <label class="form-label text-light fw-semibold" for="base">Base de caja</label>
                    <div class="input-group">
                        <span class="input-group-text admin-prefijo">$</span>
                        <input id="base" type="number" min="0" step="1" wire:model="base"
                               wire:keydown.enter.prevent="abrirCaja"
                               class="form-control form-control-touch pos-cobro-monto" autofocus>
                    </div>
                    @error('base') <p class="admin-error">{{ $message }}</p> @enderror
                    <p class="small m-0 mt-1" style="color: var(--pos-text-muted);">
                        Se propone lo contado al cerrar el turno anterior. Puedes cambiarlo.
                    </p>

                    <label class="form-label text-light fw-semibold mt-3" for="obsApertura">
                        Observaciones <span class="fw-normal" style="color: var(--pos-text-muted);">(opcional)</span>
                    </label>
                    <textarea id="obsApertura" rows="2" wire:model="observacionesApertura"
                              class="form-control form-control-touch"
                              placeholder="Ej. faltaban monedas de $500"></textarea>
                    @error('observacionesApertura') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="admin-modal-pie">
                    <button type="button" wire:click="abrirCaja" class="btn btn-touch btn-touch-primary flex-grow-1"
                            wire:loading.attr="disabled" wire:target="abrirCaja">
                        <i class="fa-solid fa-lock-open"></i> Abrir caja
                    </button>
                    <button type="button" wire:click="cancelarApertura" class="btn btn-touch admin-btn-secundario">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ DIÁLOGO: SALIDA DE CAJA ============ --}}
    @if($registrandoEgreso)
        <div class="admin-modal-fondo" wire:key="dialogo-egreso">
            <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="tituloEgreso">
                <div class="admin-modal-cabecera">
                    <h2 class="admin-panel-titulo m-0" id="tituloEgreso">
                        <i class="fa-solid fa-hand-holding-dollar" style="color: var(--pos-danger);"></i> Salida de caja
                    </h2>
                    <button type="button" class="btn-close btn-close-white"
                            wire:click="cancelarEgreso" aria-label="Cancelar"></button>
                </div>

                <div class="admin-modal-cuerpo">
                    <label class="form-label text-light fw-semibold" for="tipoEgreso">Tipo</label>
                    <select id="tipoEgreso" wire:model="tipoEgreso" class="form-select form-select-touch">
                        @foreach($tiposDeEgreso as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <label class="form-label text-light fw-semibold mt-3" for="concepto">Concepto</label>
                    <input id="concepto" type="text" wire:model="descripcionEgreso"
                           class="form-control form-control-touch"
                           placeholder="Ej. pago domiciliario turno noche" autofocus>
                    @error('descripcionEgreso') <p class="admin-error">{{ $message }}</p> @enderror

                    <label class="form-label text-light fw-semibold mt-3" for="montoEgreso">Monto</label>
                    <div class="input-group">
                        <span class="input-group-text admin-prefijo">$</span>
                        <input id="montoEgreso" type="number" min="1" step="1"
                               wire:model.live.debounce.400ms="montoEgreso"
                               class="form-control form-control-touch pos-cobro-monto">
                    </div>
                    @error('montoEgreso') <p class="admin-error">{{ $message }}</p> @enderror

                    @if($saldoNegativo)
                        {{-- Se avisa pero no se bloquea: el dinero pudo entrar por una
                             vía que el sistema todavía no conoce, y registrar la
                             realidad importa más que impedir una operación válida. --}}
                        <div class="alert admin-alerta-error d-flex align-items-start gap-2 py-2 mt-3 mb-0">
                            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                            <span>Este monto supera el efectivo esperado: la caja quedará en negativo.</span>
                        </div>
                    @endif
                </div>

                <div class="admin-modal-pie">
                    <button type="button" wire:click="registrarEgreso" class="btn btn-touch btn-touch-danger flex-grow-1"
                            wire:loading.attr="disabled" wire:target="registrarEgreso">
                        <i class="fa-solid fa-check"></i> Registrar salida
                    </button>
                    <button type="button" wire:click="cancelarEgreso" class="btn btn-touch admin-btn-secundario">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
