<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deja pedidos y caja listos para crecer sin volver a tocar el esquema.
 *
 * Se corrigen cinco cosas que habrían obligado a rediseñar más adelante:
 *
 * 1. numero_pedido era un varchar con UNIQUE global. En cuanto dos negocios
 *    emitieran su pedido "1" el segundo fallaba. Pasa a un entero
 *    numero_secuencial, único por negocio, y el prefijo queda en presentación.
 *
 * 2. Un pedido no sabía a qué turno de caja pertenecía, así que era imposible
 *    cuadrar la caja del turno. Se añade turno_caja_id.
 *
 * 3. detalle_pedidos no guardaba el nombre del producto vendido. Renombrar un
 *    producto reescribía el histórico en silencio. Se guarda una copia.
 *
 * 4. negocio_id era nullable en detalle_pedidos y gasto_cajas, lo que dejaba
 *    escapar filas fuera del aislamiento multi-tenant.
 *
 * 5. Faltaban los índices de las consultas que estos módulos harán a diario.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pedidosAntes = DB::table('pedidos')->count();
        $detallesAntes = DB::table('detalle_pedidos')->count();

        // --- 1. Consecutivo entero, único por negocio ---
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_secuencial')->default(0)->after('negocio_id');
        });

        // Se conserva el consecutivo que hubiera, extrayendo sus dígitos.
        DB::statement("
            UPDATE pedidos
            SET numero_secuencial = COALESCE(NULLIF(regexp_replace(numero_pedido, '\\D', '', 'g'), '')::bigint, id)
        ");

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropUnique('pedidos_numero_pedido_unique');
            $table->dropColumn('numero_pedido');
            $table->unique(['negocio_id', 'numero_secuencial'], 'pedidos_negocio_numero_unico');
        });

        // --- 2. Vínculo con el turno de caja ---
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('turno_caja_id')
                ->nullable()
                ->after('user_id')
                ->constrained('turno_cajas')
                ->nullOnDelete();
        });

        // --- 3. Copia del nombre vendido ---
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->string('nombre_producto')->nullable()->after('producto_id');
        });

        DB::statement('
            UPDATE detalle_pedidos d
            SET nombre_producto = p.nombre
            FROM productos p
            WHERE p.id = d.producto_id AND d.nombre_producto IS NULL
        ');

        // --- 4. El negocio deja de ser opcional ---
        $this->rellenarNegocioFaltante();

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('negocio_id')->nullable(false)->change();
        });

        Schema::table('gasto_cajas', function (Blueprint $table) {
            $table->unsignedBigInteger('negocio_id')->nullable(false)->change();
        });

        // --- 5. Índices de las consultas diarias ---
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index(['negocio_id', 'created_at'], 'pedidos_negocio_fecha_idx');
            $table->index('turno_caja_id', 'pedidos_turno_idx');
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->index('pedido_id', 'detalle_pedidos_pedido_idx');
            $table->index(['negocio_id', 'producto_id'], 'detalle_pedidos_negocio_producto_idx');
        });

        Schema::table('turno_cajas', function (Blueprint $table) {
            $table->index(['negocio_id', 'estado'], 'turno_cajas_negocio_estado_idx');
        });

        Schema::table('gasto_cajas', function (Blueprint $table) {
            $table->index('turno_caja_id', 'gasto_cajas_turno_idx');
        });

        $this->verificar($pedidosAntes, $detallesAntes);
    }

    public function down(): void
    {
        Schema::table('gasto_cajas', function (Blueprint $table) {
            $table->dropIndex('gasto_cajas_turno_idx');
            $table->unsignedBigInteger('negocio_id')->nullable()->change();
        });

        Schema::table('turno_cajas', function (Blueprint $table) {
            $table->dropIndex('turno_cajas_negocio_estado_idx');
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->dropIndex('detalle_pedidos_pedido_idx');
            $table->dropIndex('detalle_pedidos_negocio_producto_idx');
            $table->unsignedBigInteger('negocio_id')->nullable()->change();
            $table->dropColumn('nombre_producto');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_negocio_fecha_idx');
            $table->dropIndex('pedidos_turno_idx');
            $table->dropConstrainedForeignId('turno_caja_id');
            $table->dropUnique('pedidos_negocio_numero_unico');
            $table->string('numero_pedido')->nullable()->after('negocio_id');
        });

        DB::statement("UPDATE pedidos SET numero_pedido = numero_secuencial::text");

        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('numero_pedido')->nullable(false)->change();
            $table->unique('numero_pedido', 'pedidos_numero_pedido_unique');
            $table->dropColumn('numero_secuencial');
        });
    }

    /**
     * Antes de prohibir el nulo hay que asegurarse de que no queda ninguno.
     */
    private function rellenarNegocioFaltante(): void
    {
        DB::statement('
            UPDATE detalle_pedidos d
            SET negocio_id = p.negocio_id
            FROM pedidos p
            WHERE p.id = d.pedido_id AND d.negocio_id IS NULL
        ');

        DB::statement('
            UPDATE gasto_cajas g
            SET negocio_id = t.negocio_id
            FROM turno_cajas t
            WHERE t.id = g.turno_caja_id AND g.negocio_id IS NULL
        ');
    }

    private function verificar(int $pedidosAntes, int $detallesAntes): void
    {
        $pedidosDespues = DB::table('pedidos')->count();
        $detallesDespues = DB::table('detalle_pedidos')->count();

        if ($pedidosAntes !== $pedidosDespues || $detallesAntes !== $detallesDespues) {
            throw new RuntimeException(
                "Migración abortada para no perder datos. Pedidos {$pedidosAntes} → {$pedidosDespues}, ".
                "detalles {$detallesAntes} → {$detallesDespues}."
            );
        }

        $sinNumero = DB::table('pedidos')->where('numero_secuencial', 0)->count();

        if ($sinNumero > 0) {
            throw new RuntimeException("Migración abortada: {$sinNumero} pedidos se quedaron sin consecutivo.");
        }
    }
};
