<?php

namespace App\Livewire\Auth;

use App\Enums\RolUsuario;
use App\Support\TenantContext;
use Livewire\Component;

/**
 * Elección del negocio sobre el que se va a trabajar.
 *
 * Aparece cuando el usuario alcanza más de un negocio, o cuando es
 * superadministrador y por tanto no tiene ninguno asignado por defecto.
 */
class SeleccionNegocio extends Component
{
    public function elegir(int $negocioId): void
    {
        $usuario = auth()->user();

        // Se revalida en servidor: el id llega del navegador.
        if (! $usuario->puedeAccederA($negocioId)) {
            return;
        }

        app(TenantContext::class)->cambiarA($negocioId);

        $negocio = $usuario->negociosVisibles()->whereKey($negocioId)->first();
        session()->put('tenant_nombre', $negocio?->nombre);

        $this->redirect($this->destino(), navigate: true);
    }

    private function destino(): string
    {
        $rol = RolUsuario::tryFrom((string) session('rol_activo'));

        return $rol?->administraNegocio()
            ? route('admin.dashboard')
            : route('pos.main');
    }

    public function render()
    {
        return view('livewire.auth.seleccion-negocio', [
            'negocios' => auth()->user()
                ->negociosVisibles()
                ->orderBy('nombre')
                ->get(),
        ])->layout('components.layouts.limpio');
    }
}
