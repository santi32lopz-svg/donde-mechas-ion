<?php

namespace App\Livewire\Admin\Productos;

use App\Models\Etiqueta;
use App\Models\Producto;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRUD de productos del negocio activo.
 *
 * El diálogo para crear una etiqueta vive en este mismo componente y no en uno
 * aparte. Es intencionado: al compartir instancia, abrir y cerrar el modal no
 * toca las propiedades del formulario de producto, así que lo ya escrito
 * sobrevive. Con un componente separado habría que sincronizar estado entre
 * ambos y el riesgo de perder lo tecleado sería real.
 *
 * Ninguna consulta filtra por negocio_id: lo hace el scope global a partir del
 * negocio activo.
 */
class Index extends Component
{
    use WithPagination;

    public string $busqueda = '';

    // --- Formulario de producto ---
    public bool $mostrandoFormulario = false;

    #[Locked]
    public ?int $productoEnEdicion = null;

    public string $nombre = '';

    public string $precio = '';

    public ?int $etiquetaId = null;

    public string $codigoBarras = '';

    public bool $disponible = true;

    // --- Diálogo rápido de etiqueta ---
    public bool $mostrandoModalEtiqueta = false;

    public string $nuevaEtiquetaNombre = '';

    // --- Borrado ---
    #[Locked]
    public ?int $productoAEliminar = null;

    /** Avisos en propiedades: un flash no se vería sin recargar el layout. */
    public ?string $mensajeExito = null;

    public ?string $mensajeError = null;

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function abrirFormulario(?int $productoId = null): void
    {
        $this->limpiarAvisos();
        $this->resetValidation();
        $this->reset(['nombre', 'precio', 'codigoBarras', 'productoEnEdicion']);
        $this->etiquetaId = null;
        $this->disponible = true;

        if ($productoId !== null) {
            $producto = Producto::query()->find($productoId);

            if ($producto === null) {
                return;
            }

            $this->productoEnEdicion = $producto->id;
            $this->nombre = $producto->nombre;
            $this->precio = (string) (int) $producto->precio;
            $this->etiquetaId = $producto->etiqueta_id;
            $this->codigoBarras = (string) $producto->codigo_barras;
            $this->disponible = $producto->disponible;
        }

        $this->mostrandoFormulario = true;
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
        $this->mostrandoModalEtiqueta = false;
        $this->reset(['nombre', 'precio', 'codigoBarras', 'productoEnEdicion', 'nuevaEtiquetaNombre']);
        $this->etiquetaId = null;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->limpiarAvisos();

        $datos = $this->validate($this->reglas(), attributes: [
            'nombre' => 'nombre del producto',
            'precio' => 'precio',
            'etiquetaId' => 'etiqueta',
            'codigoBarras' => 'código de barras',
        ]);

        $atributos = [
            'nombre' => $datos['nombre'],
            'precio' => $datos['precio'],
            'etiqueta_id' => $datos['etiquetaId'],
            'codigo_barras' => $datos['codigoBarras'] ?: null,
            'disponible' => $datos['disponible'],
        ];

        if ($this->productoEnEdicion !== null) {
            Producto::query()->findOrFail($this->productoEnEdicion)->update($atributos);
            $this->mensajeExito = 'Producto actualizado.';
        } else {
            // negocio_id lo pone el trait BelongsToNegocio.
            Producto::create($atributos);
            $this->mensajeExito = "Producto «{$datos['nombre']}» creado. Ya aparece en el POS.";
        }

        $this->cancelar();
    }

    // ------------------------------------------------------------------
    // Diálogo rápido de etiqueta
    // ------------------------------------------------------------------

    public function abrirModalEtiqueta(): void
    {
        $this->resetValidation('nuevaEtiquetaNombre');
        $this->nuevaEtiquetaNombre = '';
        $this->mostrandoModalEtiqueta = true;
    }

    public function cerrarModalEtiqueta(): void
    {
        $this->mostrandoModalEtiqueta = false;
        $this->nuevaEtiquetaNombre = '';
        $this->resetValidation('nuevaEtiquetaNombre');
    }

    /**
     * Crea la etiqueta y la deja seleccionada en el formulario, que conserva
     * intacto lo que el usuario ya había escrito.
     */
    public function guardarEtiqueta(): void
    {
        $this->validate(
            ['nuevaEtiquetaNombre' => ['required', 'string', 'min:2', 'max:255']],
            attributes: ['nuevaEtiquetaNombre' => 'nombre de la etiqueta'],
        );

        $etiqueta = Etiqueta::create([
            'nombre' => $this->nuevaEtiquetaNombre,
            'orden_visualizacion' => (int) Etiqueta::query()->max('orden_visualizacion') + 1,
            'activo' => true,
        ]);

        $this->etiquetaId = $etiqueta->id;
        $this->mostrandoModalEtiqueta = false;
        $this->nuevaEtiquetaNombre = '';
    }

    // ------------------------------------------------------------------
    // Borrado
    // ------------------------------------------------------------------

    public function confirmarEliminacion(int $productoId): void
    {
        $this->productoAEliminar = $productoId;
    }

    public function cancelarEliminacion(): void
    {
        $this->productoAEliminar = null;
    }

    public function eliminar(): void
    {
        $this->limpiarAvisos();

        $producto = Producto::query()->find($this->productoAEliminar);

        if ($producto === null) {
            $this->productoAEliminar = null;

            return;
        }

        // Un producto vendido no se borra: falsearía el histórico y la clave
        // foránea de detalle_pedidos lo rechazaría con un error crudo.
        if (! $producto->sePuedeEliminar()) {
            $this->mensajeError = sprintf(
                'No se puede eliminar «%s» porque ya aparece en pedidos registrados. '.
                'Márcalo como no disponible para retirarlo del POS sin tocar el histórico.',
                $producto->nombre,
            );

            $this->productoAEliminar = null;

            return;
        }

        $nombre = $producto->nombre;
        $producto->delete();

        $this->mensajeExito = "Producto «{$nombre}» eliminado.";
        $this->productoAEliminar = null;
    }

    /**
     * Atajo del listado para retirar un producto del POS sin borrarlo.
     */
    public function alternarDisponibilidad(int $productoId): void
    {
        $this->limpiarAvisos();

        $producto = Producto::query()->find($productoId);

        if ($producto === null) {
            return;
        }

        $producto->update(['disponible' => ! $producto->disponible]);

        $this->mensajeExito = $producto->disponible
            ? "«{$producto->nombre}» vuelve a estar disponible en el POS."
            : "«{$producto->nombre}» ya no aparece en el POS.";
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function reglas(): array
    {
        $negocioId = app(TenantContext::class)->negocioId();

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999'],
            // La etiqueta se valida contra la consulta con scope: comprobarla
            // con Rule::exists a secas dejaría pasar la de otro negocio.
            'etiquetaId' => [
                'required',
                'integer',
                function (string $atributo, mixed $valor, callable $falla) {
                    if (! Etiqueta::query()->whereKey($valor)->exists()) {
                        $falla('La etiqueta seleccionada no existe en este negocio.');
                    }
                },
            ],
            'codigoBarras' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('productos', 'codigo_barras')
                    ->where('negocio_id', $negocioId)
                    ->ignore($this->productoEnEdicion),
            ],
            'disponible' => ['boolean'],
        ];
    }

    private function limpiarAvisos(): void
    {
        $this->mensajeExito = null;
        $this->mensajeError = null;
    }

    public function render()
    {
        return view('livewire.admin.productos.index', [
            'productos' => Producto::query()
                ->with('etiqueta:id,nombre')
                ->buscar($this->busqueda)
                ->orderBy('nombre')
                ->paginate(15),
            'etiquetas' => Etiqueta::query()->orderBy('nombre')->get(['id', 'nombre']),
        ])->layout('components.layouts.admin');
    }
}
