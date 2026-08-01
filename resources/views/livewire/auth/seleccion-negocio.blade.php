<div>
    <div class="text-center mb-4">
        <h1 class="font-brand text-uppercase text-white mb-1">Selecciona un negocio</h1>
        <p class="m-0" style="color: var(--pos-text-muted);">
            Trabajarás sobre el negocio que elijas. Podrás cambiarlo desde la barra superior.
        </p>
    </div>

    <div class="tarjetas-rol">
        @forelse($negocios as $negocio)
            <button type="button" wire:click="elegir({{ $negocio->id }})"
                    wire:key="negocio-{{ $negocio->id }}" class="tarjeta-rol">
                <span class="tarjeta-rol-icono"><i class="fa-solid fa-store"></i></span>
                <span class="tarjeta-rol-titulo">{{ $negocio->nombre }}</span>
                <span class="tarjeta-rol-texto">
                    {{ $negocio->descripcion ?: 'Sin descripción' }}
                </span>
                @if($negocio->estado_suscripcion !== 'activo')
                    <span class="badge badge-status-pending mt-2">{{ ucfirst($negocio->estado_suscripcion) }}</span>
                @endif
            </button>
        @empty
            <p class="text-center m-0" style="color: var(--pos-text-muted);">
                No tienes negocios disponibles todavía.
            </p>
        @endforelse
    </div>
</div>
