<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos que necesita el formulario de creación de negocios.
 *
 * El "estado" del formulario no añade columna: reutiliza estado_suscripcion,
 * que ya distingue activo, suspendido y prueba. Un booleano aparte crearía dos
 * fuentes de verdad sobre lo mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('nombre');
            // El almacenamiento del archivo llega más adelante; la columna se
            // deja lista para no tener que migrar de nuevo.
            $table->string('logo_path')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'logo_path']);
        });
    }
};
