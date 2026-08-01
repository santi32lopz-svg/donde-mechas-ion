<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Secuencia de pedidos independiente para cada negocio.
 *
 * Calcular el consecutivo con MAX(numero) + 1 dentro de la venta parece más
 * simple, pero con dos cajeros cobrando a la vez ambos leen el mismo máximo y
 * uno de los dos falla. Una fila por negocio permite bloquearla con
 * lockForUpdate y serializar solo a quien esté pidiendo número, sin frenar el
 * resto de la aplicación.
 *
 * El contador es un entero y nunca se reinicia. El prefijo vive en el negocio y
 * solo afecta a la presentación, de modo que cambiarlo no toca los datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias_pedido', function (Blueprint $table) {
            $table->id();
            // Único: es lo que hace segura la creación de la fila bajo carrera.
            $table->foreignId('negocio_id')->unique()->constrained('negocios')->cascadeOnDelete();
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            $table->timestamps();
        });

        Schema::table('negocios', function (Blueprint $table) {
            $table->string('prefijo_pedido', 8)->nullable()->after('slug');
        });

        $this->sembrarSecuenciasYPrefijos();
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('prefijo_pedido');
        });

        Schema::dropIfExists('secuencias_pedido');
    }

    /**
     * Cada negocio existente arranca con su secuencia en cero y un prefijo
     * deducido de su nombre, para que nadie tenga que configurarlo a mano antes
     * de poder cobrar.
     */
    private function sembrarSecuenciasYPrefijos(): void
    {
        $ahora = now();

        DB::table('negocios')->orderBy('id')->chunkById(200, function ($negocios) use ($ahora) {
            foreach ($negocios as $negocio) {
                DB::table('secuencias_pedido')->insertOrIgnore([
                    'negocio_id' => $negocio->id,
                    'ultimo_numero' => 0,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);

                DB::table('negocios')
                    ->where('id', $negocio->id)
                    ->update(['prefijo_pedido' => $this->prefijoDesde($negocio->nombre)]);
            }
        });
    }

    /**
     * "Donde Mechas" da DM; "Pizza" da PI. Es solo un punto de partida
     * razonable: se cambia desde Configuración.
     */
    private function prefijoDesde(string $nombre): string
    {
        $palabras = preg_split('/\s+/', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $iniciales = collect($palabras)
            ->take(3)
            ->map(fn (string $palabra) => Str::upper(Str::substr($palabra, 0, 1)))
            ->implode('');

        if (Str::length($iniciales) >= 2) {
            return $iniciales;
        }

        return Str::upper(Str::substr(Str::ascii($nombre), 0, 2)) ?: 'PED';
    }
};
