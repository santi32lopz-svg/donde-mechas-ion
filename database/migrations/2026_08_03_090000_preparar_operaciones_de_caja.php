<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara la apertura de turno y el registro de egresos.
 *
 * Tres cosas:
 *
 * 1. Un solo turno abierto por negocio, garantizado por la base de datos y no
 *    solo por PHP. Hay un único cajón físico; dos turnos abiertos a la vez
 *    harían imposible cuadrarlo, y una comprobación en código puede perderse
 *    con dos peticiones simultáneas.
 *
 * 2. Trazabilidad de los egresos: quién los registró y de qué tipo son. Un
 *    movimiento de dinero anónimo no se puede auditar.
 *
 * 3. Observaciones de apertura, separadas de las del cierre: son dos momentos
 *    distintos y mezclarlas perdería información.
 */
return new class extends Migration
{
    private const INDICE_TURNO_ABIERTO = 'turno_cajas_uno_abierto_por_negocio';

    public function up(): void
    {
        Schema::table('turno_cajas', function (Blueprint $table) {
            $table->text('observaciones_apertura')->nullable()->after('fecha_cierre');
        });

        Schema::table('gasto_cajas', function (Blueprint $table) {
            // Quién sacó el dinero del cajón.
            $table->foreignId('user_id')->nullable()->after('turno_caja_id')
                ->constrained('users')->nullOnDelete();
            $table->string('tipo')->default('otro')->after('user_id');
            $table->index(['negocio_id', 'tipo'], 'gasto_cajas_negocio_tipo_idx');
        });

        $this->crearIndiceDeTurnoUnico();
    }

    public function down(): void
    {
        $this->eliminarIndiceDeTurnoUnico();

        Schema::table('gasto_cajas', function (Blueprint $table) {
            $table->dropIndex('gasto_cajas_negocio_tipo_idx');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('tipo');
        });

        Schema::table('turno_cajas', function (Blueprint $table) {
            $table->dropColumn('observaciones_apertura');
        });
    }

    /**
     * Índice único parcial: la unicidad solo aplica a las filas abiertas, de
     * modo que un negocio puede acumular todos los turnos cerrados que quiera
     * pero nunca tener dos abiertos.
     *
     * Es una construcción de PostgreSQL; en otros motores la garantía queda en
     * la comprobación de la aplicación.
     */
    private function crearIndiceDeTurnoUnico(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Si alguna base ya tuviera dos abiertos, el índice no podría crearse.
        // Se cierra el más antiguo antes, para no dejar la migración a medias.
        DB::statement("
            UPDATE turno_cajas
            SET estado = 'cerrado', fecha_cierre = COALESCE(fecha_cierre, NOW())
            WHERE estado = 'abierto'
              AND id NOT IN (
                  SELECT MAX(id) FROM turno_cajas WHERE estado = 'abierto' GROUP BY negocio_id
              )
        ");

        DB::statement(
            'CREATE UNIQUE INDEX '.self::INDICE_TURNO_ABIERTO.
            " ON turno_cajas (negocio_id) WHERE estado = 'abierto'"
        );
    }

    private function eliminarIndiceDeTurnoUnico(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_TURNO_ABIERTO);
    }
};
