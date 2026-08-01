<?php

use App\Enums\RolUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sustituye users.negocio_id por la tabla pivote negocio_usuario.
 *
 * users.negocio_id ataba cada usuario a un único negocio, lo que impide que un
 * administrador gestione varios y que alguien sea administrador de un negocio y
 * cajero de otro.
 *
 * La columna no se borra a ciegas: primero se vuelca a la tabla pivote y se
 * comprueba que el número de vínculos coincide con el de usuarios que tenían
 * negocio. Si no cuadra se lanza una excepción, y como PostgreSQL ejecuta las
 * migraciones dentro de una transacción, no se aplica nada: la columna sigue
 * intacta y no hay pérdida de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocio_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('rol')->default(RolUsuario::Cajero->value);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Un usuario no puede estar dos veces en el mismo negocio.
            $table->unique(['negocio_id', 'user_id'], 'negocio_usuario_unico');
            // Resolver "a qué negocios puede entrar este usuario" es la consulta
            // más frecuente de la plataforma.
            $table->index(['user_id', 'activo'], 'negocio_usuario_user_activo_idx');
        });

        $this->volcarRelacionExistente();
        $this->verificarQueNoHayPerdidaDeDatos();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('negocio_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('negocio_id')->nullable()->after('id');
        });

        // Se restaura el vínculo más antiguo de cada usuario, que es el que
        // originalmente ocupaba la columna.
        DB::statement('
            UPDATE users
            SET negocio_id = (
                SELECT nu.negocio_id
                FROM negocio_usuario nu
                WHERE nu.user_id = users.id
                ORDER BY nu.id
                LIMIT 1
            )
        ');

        Schema::dropIfExists('negocio_usuario');
    }

    /**
     * Traslada users.negocio_id a la tabla pivote.
     *
     * El rol dentro del negocio se deduce del rol que el usuario ya tenía:
     * quien administraba sigue administrando, el resto entra como cajero.
     */
    private function volcarRelacionExistente(): void
    {
        $ahora = now();

        DB::table('users')
            ->whereNotNull('negocio_id')
            ->orderBy('id')
            ->chunkById(500, function ($usuarios) use ($ahora) {
                $filas = [];

                foreach ($usuarios as $usuario) {
                    $filas[] = [
                        'negocio_id' => $usuario->negocio_id,
                        'user_id' => $usuario->id,
                        'rol' => $usuario->rol === RolUsuario::Administrador->value
                            ? RolUsuario::Administrador->value
                            : RolUsuario::Cajero->value,
                        'activo' => $usuario->activo,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }

                if ($filas !== []) {
                    DB::table('negocio_usuario')->insert($filas);
                }
            });
    }

    /**
     * Corta la migración si algún vínculo se quedó por el camino.
     */
    private function verificarQueNoHayPerdidaDeDatos(): void
    {
        $esperados = DB::table('users')->whereNotNull('negocio_id')->count();
        $migrados = DB::table('negocio_usuario')->count();

        if ($migrados !== $esperados) {
            throw new RuntimeException(
                "Migración abortada para no perder datos: {$esperados} usuarios tenían negocio ".
                "pero se crearon {$migrados} vínculos en negocio_usuario. ".
                'No se ha eliminado users.negocio_id.'
            );
        }

        $huerfanos = DB::table('negocio_usuario as nu')
            ->leftJoin('negocios as n', 'n.id', '=', 'nu.negocio_id')
            ->whereNull('n.id')
            ->count();

        if ($huerfanos > 0) {
            throw new RuntimeException(
                "Migración abortada: {$huerfanos} vínculos apuntan a negocios inexistentes."
            );
        }
    }
};
