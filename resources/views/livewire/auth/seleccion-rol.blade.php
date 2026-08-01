<div>
    <div class="text-center mb-4">
        <h1 class="font-brand text-uppercase text-white mb-1">¿Cómo vas a entrar?</h1>
        <p class="m-0" style="color: var(--pos-text-muted);">
            Hola {{ auth()->user()->nombre }}, elige con qué rol quieres trabajar.
        </p>
    </div>

    <div class="tarjetas-rol">
        @foreach($roles as $rol)
            <button type="button" wire:click="elegir('{{ $rol->value }}')" class="tarjeta-rol">
                <span class="tarjeta-rol-icono">
                    @if($rol === \App\Enums\RolUsuario::Administrador)
                        <i class="fa-solid fa-sliders"></i>
                    @else
                        <i class="fa-solid fa-cash-register"></i>
                    @endif
                </span>
                <span class="tarjeta-rol-titulo">{{ $rol->etiqueta() }}</span>
                <span class="tarjeta-rol-texto">
                    @if($rol === \App\Enums\RolUsuario::Administrador)
                        Productos, etiquetas, usuarios y configuración del negocio.
                    @else
                        Terminal de venta para atender y cobrar pedidos.
                    @endif
                </span>
            </button>
        @endforeach
    </div>

    @if($roles->isEmpty())
        <p class="text-center" style="color: var(--pos-text-muted);">
            Tu cuenta no tiene ningún rol asignado en un negocio activo.
        </p>
    @endif
</div>
