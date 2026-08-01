<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    /**
     * Negocios a los que pertenece el usuario.
     *
     * Sustituye a la antigua columna users.negocio_id: un usuario puede estar
     * en varios negocios y con un rol distinto en cada uno.
     */
    public function negocios(): BelongsToMany
    {
        return $this->belongsToMany(Negocio::class, 'negocio_usuario')
            ->withPivot(['rol', 'activo'])
            ->withTimestamps();
    }

    /**
     * Negocios en los que puede trabajar ahora mismo: el vínculo está activo y
     * la suscripción del negocio al día.
     */
    public function negociosDisponibles(): BelongsToMany
    {
        return $this->negocios()
            ->wherePivot('activo', true)
            ->where('negocios.estado_suscripcion', 'activo');
    }

    public function esSuperadmin(): bool
    {
        return $this->rol === RolUsuario::Superadmin->value;
    }

    /**
     * Un superadministrador alcanza cualquier negocio; el resto, solo aquellos
     * en los que tenga un vínculo activo.
     */
    public function puedeAccederA(int $negocioId): bool
    {
        if ($this->esSuperadmin()) {
            return Negocio::query()->whereKey($negocioId)->exists();
        }

        return $this->negociosDisponibles()->whereKey($negocioId)->exists();
    }

    /**
     * Rol efectivo dentro de un negocio concreto.
     */
    public function rolEn(int $negocioId): ?RolUsuario
    {
        if ($this->esSuperadmin()) {
            return RolUsuario::Superadmin;
        }

        $negocio = $this->negociosDisponibles()->whereKey($negocioId)->first();

        return $negocio === null ? null : RolUsuario::tryFrom($negocio->pivot->rol);
    }

    /**
     * Roles que el usuario puede ejercer en alguno de sus negocios. Alimenta la
     * pantalla de selección de rol posterior al login.
     *
     * @return Collection<int, RolUsuario>
     */
    public function rolesDisponibles(): Collection
    {
        if ($this->esSuperadmin()) {
            return collect(RolUsuario::deNegocio());
        }

        return $this->negociosDisponibles()
            ->get()
            ->map(fn (Negocio $negocio) => RolUsuario::tryFrom($negocio->pivot->rol))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
}
