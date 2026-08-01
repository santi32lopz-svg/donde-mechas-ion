<div>
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">Dashboard</h1>
            <p class="admin-subtitulo">{{ $negocio?->nombre }}</p>
        </div>
        <a href="{{ route('pos.main') }}" class="btn btn-touch btn-touch-primary">
            <i class="fa-solid fa-cash-register"></i> Abrir POS
        </a>
    </div>

    <div class="admin-metricas">
        <div class="admin-metrica">
            <span class="admin-metrica-valor">{{ $totalProductos }}</span>
            <span class="admin-metrica-texto">Productos en el catálogo</span>
        </div>
        <div class="admin-metrica">
            <span class="admin-metrica-valor">{{ $productosNoDisponibles }}</span>
            <span class="admin-metrica-texto">No disponibles</span>
        </div>
        <div class="admin-metrica">
            <span class="admin-metrica-valor">{{ ucfirst($negocio?->estado_suscripcion ?? '—') }}</span>
            <span class="admin-metrica-texto">Estado de la suscripción</span>
        </div>
        <div class="admin-metrica">
            <span class="admin-metrica-valor">{{ ucfirst($negocio?->plan ?? '—') }}</span>
            <span class="admin-metrica-texto">Plan contratado</span>
        </div>
    </div>

    <div class="admin-panel mt-4">
        <h2 class="admin-panel-titulo">Siguientes pasos</h2>
        <p class="m-0" style="color: var(--pos-text-muted);">
            Los módulos de Pedidos, Caja y Usuarios están en construcción. Las cifras de
            ventas aparecerán aquí cuando el cobro esté implementado.
        </p>
    </div>
</div>
