<?php

namespace App\Livewire\Admin\MiNegocio;

use App\Enums\RolUsuario;
use App\Models\Negocio;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado y alta de negocios.
 *
 * Crear un negocio aquí no monta nada aparte: se apoya en el mismo aislamiento
 * multi-tenant que ya usa el POS, de modo que el negocio nuevo nace con sus
 * productos, etiquetas, pedidos y caja separados sin código adicional.
 */
class Index extends Component
{
    use WithPagination;

    public bool $mostrandoFormulario = false;

    /** Negocio en edición. Va bloqueado: el id no debe poder cambiarlo el cliente. */
    #[Locked]
    public ?int $negocioEnEdicion = null;

    public string $nombre = '';

    public string $descripcion = '';

    public string $estado = 'prueba';

    /** En Livewire un flash no se ve hasta recargar el layout; ver EtiquetasIndex. */
    public ?string $mensajeExito = null;

    public function abrirFormulario(?int $negocioId = null): void
    {
        $this->resetValidation();
        $this->negocioEnEdicion = null;
        $this->reset(['nombre', 'descripcion']);
        $this->estado = 'prueba';

        if ($negocioId !== null) {
            $negocio = $this->negociosVisibles()->whereKey($negocioId)->first();

            if ($negocio === null) {
                return;
            }

            $this->negocioEnEdicion = $negocio->id;
            $this->nombre = $negocio->nombre;
            $this->descripcion = (string) $negocio->descripcion;
            $this->estado = $negocio->estado_suscripcion;
        }

        $this->mostrandoFormulario = true;
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
        $this->negocioEnEdicion = null;
        $this->reset(['nombre', 'descripcion']);
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'estado' => ['required', Rule::in(['activo', 'suspendido', 'prueba'])],
        ], attributes: [
            'nombre' => 'nombre del negocio',
            'estado' => 'estado',
        ]);

        if ($this->negocioEnEdicion !== null) {
            $negocio = $this->negociosVisibles()->whereKey($this->negocioEnEdicion)->firstOrFail();

            $negocio->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?: null,
                'estado_suscripcion' => $datos['estado'],
            ]);

            $this->mensajeExito = 'Negocio actualizado.';
        } else {
            $negocio = Negocio::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?: null,
                'slug' => $this->slugDisponible($datos['nombre']),
                'plan' => 'basico',
                'estado_suscripcion' => $datos['estado'],
            ]);

            // Quien lo crea queda como su administrador, salvo el
            // superadministrador, que ya alcanza todos los negocios por su rol.
            if (! auth()->user()->esSuperadmin()) {
                auth()->user()->negocios()->attach($negocio->id, [
                    'rol' => RolUsuario::Administrador->value,
                    'activo' => true,
                ]);
            }

            $this->mensajeExito = "Negocio «{$negocio->nombre}» creado.";
        }

        $this->cancelar();
    }

    /**
     * Cambia el negocio sobre el que se está trabajando.
     */
    public function activar(int $negocioId): void
    {
        if (! auth()->user()->puedeAccederA($negocioId)) {
            return;
        }

        app(TenantContext::class)->cambiarA($negocioId);
        session()->put('tenant_nombre', Negocio::query()->find($negocioId)?->nombre);

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    /**
     * El slug alimenta URLs, así que debe ser único aunque dos negocios
     * compartan nombre.
     */
    private function slugDisponible(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'negocio';
        $slug = $base;
        $sufijo = 2;

        while (Negocio::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$sufijo++;
        }

        return $slug;
    }

    private function negociosVisibles()
    {
        return auth()->user()->negociosVisibles();
    }

    public function render()
    {
        return view('livewire.admin.mi-negocio.index', [
            'negocios' => $this->negociosVisibles()->orderBy('nombre')->paginate(12),
            'negocioActivoId' => app(TenantContext::class)->negocioId(),
        ])->layout('components.layouts.admin');
    }
}
