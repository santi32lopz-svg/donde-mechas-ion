<?php

use App\Enums\RolUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía users.rol para admitir el rol de plataforma "superadmin".
 *
 * Laravel materializa $table->enum() como una restricción CHECK, así que añadir
 * un valor obliga a recrearla. Los valores se toman de App\Enums\RolUsuario
 * para que el enum siga siendo la única fuente de verdad: añadir un rol nuevo
 * es añadir un caso allí y una migración como esta.
 */
return new class extends Migration
{
    private const RESTRICCION = 'users_rol_check';

    public function up(): void
    {
        $this->redefinirRestriccion(RolUsuario::valores());
    }

    public function down(): void
    {
        // Nadie puede quedarse con un rol que la restricción antigua no admite.
        DB::table('users')
            ->where('rol', RolUsuario::Superadmin->value)
            ->update(['rol' => RolUsuario::Administrador->value]);

        $this->redefinirRestriccion([
            RolUsuario::Administrador->value,
            RolUsuario::Cajero->value,
        ]);
    }

    /**
     * @param  array<int, string>  $valores
     */
    private function redefinirRestriccion(array $valores): void
    {
        // SQLite no aplica este tipo de restricción, así que no hay nada que
        // recrear allí; el enum de PHP sigue validando en la aplicación.
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $lista = implode(', ', array_map(
            fn (string $valor) => "'".str_replace("'", "''", $valor)."'",
            $valores
        ));

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS '.self::RESTRICCION);
        DB::statement(
            'ALTER TABLE users ADD CONSTRAINT '.self::RESTRICCION.
            " CHECK (rol::text = ANY (ARRAY[{$lista}]::text[]))"
        );
    }
};
