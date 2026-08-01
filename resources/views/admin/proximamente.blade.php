<x-layouts.admin :title="$titulo">
    <div class="admin-encabezado">
        <div>
            <h1 class="admin-titulo">{{ $titulo }}</h1>
            <p class="admin-subtitulo">{{ $descripcion }}</p>
        </div>
    </div>

    <div class="admin-panel text-center">
        <i class="fa-solid {{ $icono }} fs-1 d-block mb-3" style="color: var(--pos-text-muted);"></i>
        <h2 class="admin-panel-titulo justify-content-center">Módulo en construcción</h2>
        <p class="m-0" style="color: var(--pos-text-muted);">
            La estructura ya está reservada. Este módulo se implementará en una etapa posterior.
        </p>
    </div>
</x-layouts.admin>
