<?php

namespace App\Livewire\Admin\Etiquetas;

use App\Models\Etiqueta;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRUD de etiquetas del negocio activo.
 *
 * Las etiquetas alimentan directamente los botones de filtro del POS, así que
 * lo que se cree aquí aparece en la terminal sin ningún paso intermedio.
 *
 * Ninguna consulta filtra por negocio_id: de eso se encarga el scope global de
 * BelongsToNegocio a partir del negocio activo. Es intencionado, para que un
 * módulo nuevo no pueda olvidarse del aislamiento.
 */
class Index extends Component
{
    use WithPagination;

    public bool $mostrandoFormulario = false;

    /** El id no debe poder alterarlo el navegador. */
    #[Locked]
    public ?int $etiquetaEnEdicion = null;

    public string $nombre = '';

    public int $ordenVisualizacion = 0;

    public bool $activo = true;

    /** Etiqueta cuya eliminación está pendiente de confirmar. */
    #[Locked]
    public ?int $etiquetaAEliminar = null;

    /**
     * Los avisos van en propiedades y no en session()->flash() porque una
     * actualización de Livewire no recarga el layout: el mensaje flasheado no
     * se vería hasta la siguiente navegación completa.
     */
    public ?string $mensajeExito = null;

    public ?string $mensajeError = null;

    public function abrirFormulario(?int $etiquetaId = null): void
    {
        $this->limpiarAvisos();
        $this->resetValidation();
        $this->reset(['nombre', 'etiquetaEnEdicion']);
        $this->ordenVisualizacion = 0;
        $this->activo = true;

        if ($etiquetaId !== null) {
            $etiqueta = Etiqueta::query()->find($etiquetaId);

            if ($etiqueta === null) {
                return;
            }

            $this->etiquetaEnEdicion = $etiqueta->id;
            $this->nombre = $etiqueta->nombre;
            $this->ordenVisualizacion = $etiqueta->orden_visualizacion;
            $this->activo = $etiqueta->activo;
        }

        $this->mostrandoFormulario = true;
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
        $this->reset(['nombre', 'etiquetaEnEdicion']);
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->limpiarAvisos();

        $datos = $this->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:255'],
            'ordenVisualizacion' => ['required', 'integer', 'min:0', 'max:999'],
            'activo' => ['boolean'],
        ], attributes: [
            'nombre' => 'nombre de la etiqueta',
            'ordenVisualizacion' => 'orden',
        ]);

        $atributos = [
            'nombre' => $datos['nombre'],
            'orden_visualizacion' => $datos['ordenVisualizacion'],
            'activo' => $datos['activo'],
        ];

        if ($this->etiquetaEnEdicion !== null) {
            Etiqueta::query()->findOrFail($this->etiquetaEnEdicion)->update($atributos);
            $this->mensajeExito = 'Etiqueta actualizada.';
        } else {
            // negocio_id lo rellena el trait BelongsToNegocio con el negocio activo.
            Etiqueta::create($atributos);
            $this->mensajeExito = "Etiqueta «{$datos['nombre']}» creada. Ya aparece en el POS.";
        }

        $this->cancelar();
    }

    public function confirmarEliminacion(int $etiquetaId): void
    {
        $this->etiquetaAEliminar = $etiquetaId;
    }

    public function cancelarEliminacion(): void
    {
        $this->etiquetaAEliminar = null;
    }

    /**
     * La clave foránea es RESTRICT, así que la base rechazaría el borrado de una
     * etiqueta con productos. Se comprueba antes para poder explicar el motivo
     * en lugar de dejar escapar un error de integridad.
     */
    public function eliminar(): void
    {
        $this->limpiarAvisos();

        $etiqueta = Etiqueta::query()->find($this->etiquetaAEliminar);

        if ($etiqueta === null) {
            $this->etiquetaAEliminar = null;

            return;
        }

        if (! $etiqueta->sePuedeEliminar()) {
            $cuantos = $etiqueta->productos()->count();

            $this->mensajeError = sprintf(
                'No se puede eliminar «%s» porque tiene %d producto%s asociado%s. '.
                'Muévelos a otra etiqueta primero.',
                $etiqueta->nombre,
                $cuantos,
                $cuantos === 1 ? '' : 's',
                $cuantos === 1 ? '' : 's',
            );

            $this->etiquetaAEliminar = null;

            return;
        }

        $nombre = $etiqueta->nombre;
        $etiqueta->delete();

        $this->mensajeExito = "Etiqueta «{$nombre}» eliminada.";
        $this->etiquetaAEliminar = null;
    }

    private function limpiarAvisos(): void
    {
        $this->mensajeExito = null;
        $this->mensajeError = null;
    }

    public function render()
    {
        return view('livewire.admin.etiquetas.index', [
            'etiquetas' => Etiqueta::query()
                ->withCount('productos')
                ->orderBy('orden_visualizacion')
                ->orderBy('nombre')
                ->paginate(15),
        ])->layout('components.layouts.admin');
    }
}
