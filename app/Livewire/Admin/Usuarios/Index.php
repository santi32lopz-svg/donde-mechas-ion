<?php

namespace App\Livewire\Admin\Usuarios;

use App\Enums\RolUsuario;
use App\Models\Negocio;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Quién puede entrar al negocio activo y con qué rol.
 *
 * El vínculo vive en la tabla pivote negocio_usuario, así que dar de alta a
 * alguien aquí no toca los demás negocios a los que pertenezca: una misma
 * persona puede ser administradora en uno y cajera en otro.
 *
 * Al añadir a alguien por correo pueden pasar dos cosas: si ya tiene cuenta en
 * la plataforma se le vincula sin tocar su contraseña ni su nombre, y si no,
 * se crea. Lo contrario obligaría a cada negocio a inventar cuentas nuevas para
 * gente que ya trabaja en otro.
 */
class Index extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public bool $mostrandoFormulario = false;

    /** Vínculo en edición: se identifica por usuario, no por fila del pivote. */
    #[Locked]
    public ?int $usuarioEnEdicion = null;

    public string $nombre = '';

    public string $email = '';

    public string $password = '';

    public string $rol = RolUsuario::Cajero->value;

    #[Locked]
    public ?int $usuarioADesvincular = null;

    public ?string $mensajeExito = null;

    public ?string $mensajeError = null;

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function abrirFormulario(?int $usuarioId = null): void
    {
        $this->limpiarAvisos();
        $this->resetValidation();
        $this->reset(['nombre', 'email', 'password', 'usuarioEnEdicion']);
        $this->rol = RolUsuario::Cajero->value;

        if ($usuarioId !== null) {
            $usuario = $this->miembros()->whereKey($usuarioId)->first();

            if ($usuario === null) {
                return;
            }

            $this->usuarioEnEdicion = $usuario->id;
            $this->nombre = $usuario->nombre;
            $this->email = $usuario->email;
            $this->rol = $usuario->pivot->rol;
        }

        $this->mostrandoFormulario = true;
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
        $this->reset(['nombre', 'email', 'password', 'usuarioEnEdicion']);
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->limpiarAvisos();

        // Al editar solo se cambia el rol dentro de este negocio. El nombre y la
        // contraseña son de la persona, no del negocio, y tocarlos desde aquí
        // afectaría a los demás negocios en los que trabaje.
        if ($this->usuarioEnEdicion !== null) {
            $this->cambiarRol();

            return;
        }

        $datos = $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'rol' => ['required', Rule::in(array_column(RolUsuario::deNegocio(), 'value'))],
        ], attributes: ['email' => 'correo', 'rol' => 'rol']);

        $usuario = User::query()->where('email', $datos['email'])->first();

        if ($usuario !== null && $usuario->esSuperadmin()) {
            $this->mensajeError = 'Ese correo pertenece a un administrador de plataforma, '.
                'que ya alcanza todos los negocios y no necesita vincularse.';

            return;
        }

        if ($usuario !== null && $this->negocio()->usuarios()->whereKey($usuario->id)->exists()) {
            $this->mensajeError = 'Esa persona ya forma parte de este negocio.';

            return;
        }

        if ($usuario === null) {
            // Cuenta nueva: ahora sí hacen falta nombre y contraseña.
            $this->validate([
                'nombre' => ['required', 'string', 'min:3', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ], attributes: ['nombre' => 'nombre', 'password' => 'contraseña']);

            $usuario = User::create([
                'nombre' => $this->nombre,
                'email' => $datos['email'],
                'password' => Hash::make($this->password),
                'rol' => $datos['rol'],
                'activo' => true,
            ]);

            $this->mensajeExito = "Cuenta creada para {$usuario->nombre} y vinculada al negocio.";
        } else {
            $this->mensajeExito = "{$usuario->nombre} ya tenía cuenta y se vinculó a este negocio.";
        }

        $this->negocio()->usuarios()->attach($usuario->id, [
            'rol' => $datos['rol'],
            'activo' => true,
        ]);

        $this->cancelar();
    }

    /**
     * Cambia el rol de un miembro dentro de este negocio.
     */
    private function cambiarRol(): void
    {
        $datos = $this->validate([
            'rol' => ['required', Rule::in(array_column(RolUsuario::deNegocio(), 'value'))],
        ], attributes: ['rol' => 'rol']);

        $usuario = $this->miembros()->whereKey($this->usuarioEnEdicion)->first();

        if ($usuario === null) {
            $this->cancelar();

            return;
        }

        // Quitarle el rol de administrador al último dejaría el negocio sin
        // nadie que pueda gestionarlo.
        if ($datos['rol'] !== RolUsuario::Administrador->value && $this->esElUltimoAdministrador($usuario)) {
            $this->mensajeError = 'No se puede quitar el rol de administrador: '.
                'el negocio se quedaría sin nadie que pueda gestionarlo.';
            $this->cancelar();

            return;
        }

        $this->negocio()->usuarios()->updateExistingPivot($usuario->id, ['rol' => $datos['rol']]);

        $this->mensajeExito = "Rol de {$usuario->nombre} actualizado.";
        $this->cancelar();
    }

    /**
     * Suspende o restaura el acceso sin romper el vínculo ni el histórico.
     */
    public function alternarAcceso(int $usuarioId): void
    {
        $this->limpiarAvisos();

        $usuario = $this->miembros()->whereKey($usuarioId)->first();

        if ($usuario === null) {
            return;
        }

        if ($usuario->id === auth()->id()) {
            $this->mensajeError = 'No puedes suspender tu propio acceso.';

            return;
        }

        $activo = (bool) $usuario->pivot->activo;

        if ($activo && $this->esElUltimoAdministrador($usuario)) {
            $this->mensajeError = 'No se puede suspender al último administrador del negocio.';

            return;
        }

        $this->negocio()->usuarios()->updateExistingPivot($usuario->id, ['activo' => ! $activo]);

        $this->mensajeExito = $activo
            ? "Se suspendió el acceso de {$usuario->nombre}."
            : "Se restauró el acceso de {$usuario->nombre}.";
    }

    public function confirmarDesvinculacion(int $usuarioId): void
    {
        $this->usuarioADesvincular = $usuarioId;
    }

    public function cancelarDesvinculacion(): void
    {
        $this->usuarioADesvincular = null;
    }

    /**
     * Saca a alguien de este negocio. La cuenta sigue existiendo, porque puede
     * estar trabajando en otros.
     */
    public function desvincular(): void
    {
        $this->limpiarAvisos();

        $usuario = $this->miembros()->whereKey($this->usuarioADesvincular)->first();
        $this->usuarioADesvincular = null;

        if ($usuario === null) {
            return;
        }

        if ($usuario->id === auth()->id()) {
            $this->mensajeError = 'No puedes desvincularte a ti mismo del negocio.';

            return;
        }

        if ($this->esElUltimoAdministrador($usuario)) {
            $this->mensajeError = 'No se puede desvincular al último administrador del negocio.';

            return;
        }

        $this->negocio()->usuarios()->detach($usuario->id);

        $this->mensajeExito = "{$usuario->nombre} ya no forma parte de este negocio.";
    }

    /**
     * Comprueba si el usuario es el único administrador activo que le queda al
     * negocio. Sin esta salvaguarda es posible dejarlo sin quien lo gestione.
     */
    private function esElUltimoAdministrador(User $usuario): bool
    {
        if ($usuario->pivot->rol !== RolUsuario::Administrador->value) {
            return false;
        }

        $administradores = $this->negocio()->usuarios()
            ->wherePivot('rol', RolUsuario::Administrador->value)
            ->wherePivot('activo', true)
            ->count();

        return $administradores <= 1;
    }

    private function negocio(): Negocio
    {
        return app(TenantContext::class)->negocio();
    }

    private function miembros()
    {
        return $this->negocio()->usuarios();
    }

    private function limpiarAvisos(): void
    {
        $this->mensajeExito = null;
        $this->mensajeError = null;
    }

    public function render()
    {
        $consulta = $this->miembros();

        if (trim($this->busqueda) !== '') {
            $termino = '%'.addcslashes(trim($this->busqueda), '%_\\').'%';
            $operador = $consulta->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $consulta->where(function ($sub) use ($operador, $termino) {
                $sub->where('users.nombre', $operador, $termino)
                    ->orWhere('users.email', $operador, $termino);
            });
        }

        return view('livewire.admin.usuarios.index', [
            'usuarios' => $consulta->orderBy('users.nombre')->paginate(15),
            'rolesDisponibles' => RolUsuario::deNegocio(),
            'usuarioActualId' => auth()->id(),
        ])->layout('components.layouts.admin');
    }
}
