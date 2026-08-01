<?php

namespace App\Livewire\Auth;

use App\Enums\RolUsuario;
use App\Support\TenantContext;
use Livewire\Component;

/**
 * Pantalla posterior al login: con qué sombrero entra el usuario.
 *
 * Un mismo usuario puede ser administrador de un negocio y cajero de otro, así
 * que el rol no se deduce: se elige. Solo se ofrecen los roles que realmente
 * puede ejercer, y si solo tiene uno se salta la pantalla para no obligarle a
 * un clic sin decisión.
 */
class SeleccionRol extends Component
{
    public function mount(): void
    {
        $roles = auth()->user()->rolesDisponibles();

        if ($roles->count() === 1) {
            $this->redirect($this->destinoPara($roles->first()), navigate: true);
        }
    }

    public function elegir(string $rol): void
    {
        $seleccionado = RolUsuario::tryFrom($rol);

        // Nadie debería poder entrar como administrador si solo es cajero.
        if ($seleccionado === null || ! auth()->user()->rolesDisponibles()->contains($seleccionado)) {
            return;
        }

        session()->put('rol_activo', $seleccionado->value);

        $this->redirect($this->destinoPara($seleccionado), navigate: true);
    }

    /**
     * El administrador necesita un negocio sobre el que operar; el cajero entra
     * directo al POS si solo tiene uno.
     */
    private function destinoPara(RolUsuario $rol): string
    {
        session()->put('rol_activo', $rol->value);

        if (app(TenantContext::class)->negocioId() === null) {
            return route('negocio.seleccionar');
        }

        return $rol->administraNegocio()
            ? route('admin.dashboard')
            : route('pos.main');
    }

    public function render()
    {
        return view('livewire.auth.seleccion-rol', [
            'roles' => auth()->user()->rolesDisponibles(),
        ])->layout('components.layouts.limpio');
    }
}
