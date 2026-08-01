<?php

namespace App\Livewire\Admin;

use App\Support\TenantContext;
use Livewire\Component;

/**
 * Datos operativos del negocio activo.
 *
 * El plan y el estado de la suscripción se muestran pero no se editan aquí:
 * pertenecen a la gestión de la cartera de negocios y se cambian desde Mi
 * Negocio. Tener dos pantallas que escriban lo mismo invita a que diverjan.
 *
 * El slug tampoco se regenera al cambiar el nombre: alimenta URLs y renombrar
 * un negocio no debería romper enlaces ya compartidos.
 */
class Configuracion extends Component
{
    public string $nombre = '';

    public string $descripcion = '';

    public string $nitRut = '';

    public string $telefono = '';

    public string $direccion = '';

    public string $prefijoPedido = '';

    public ?string $mensajeExito = null;

    public function mount(): void
    {
        $negocio = app(TenantContext::class)->negocio();

        $this->nombre = (string) $negocio?->nombre;
        $this->descripcion = (string) $negocio?->descripcion;
        $this->nitRut = (string) $negocio?->nit_rut;
        $this->telefono = (string) $negocio?->telefono;
        $this->direccion = (string) $negocio?->direccion;
        $this->prefijoPedido = (string) $negocio?->prefijo_pedido;
    }

    public function guardar(): void
    {
        $this->mensajeExito = null;

        $datos = $this->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'nitRut' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            // Solo letras y números: acaba impreso en la tirilla junto al
            // consecutivo, y un separador ahí confundiría al leerlo.
            'prefijoPedido' => ['nullable', 'string', 'max:8', 'regex:/^[A-Za-z0-9]+$/'],
        ], attributes: [
            'nombre' => 'nombre del negocio',
            'nitRut' => 'NIT o RUT',
            'prefijoPedido' => 'prefijo de pedidos',
        ]);

        app(TenantContext::class)->negocio()->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?: null,
            'nit_rut' => $datos['nitRut'] ?: null,
            'telefono' => $datos['telefono'] ?: null,
            'direccion' => $datos['direccion'] ?: null,
            'prefijo_pedido' => strtoupper($datos['prefijoPedido'] ?: '') ?: null,
        ]);

        // La barra superior lee el nombre de la sesión.
        session()->put('tenant_nombre', $datos['nombre']);

        $this->mensajeExito = 'Configuración guardada.';
    }

    public function render()
    {
        return view('livewire.admin.configuracion', [
            'negocio' => app(TenantContext::class)->negocio(),
        ])->layout('components.layouts.admin');
    }
}
