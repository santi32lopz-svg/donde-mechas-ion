<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca las líneas vendidas de un producto que ya no estaba disponible.
 *
 * En comida rápida el producto suele estar servido antes de que el cajero
 * termine de cobrar, así que bloquear la venta porque alguien lo ocultó del POS
 * a media transacción castiga al cliente por un problema de gestión.
 *
 * Se permite cobrar y se deja constancia. Va en una columna propia y no en
 * pedidos.notas porque el texto libre no se puede consultar: con esto se puede
 * responder "qué se vendió sin disponibilidad este mes" con un WHERE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->boolean('vendido_sin_disponibilidad')
                ->default(false)
                ->after('nombre_producto');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->dropColumn('vendido_sin_disponibilidad');
        });
    }
};
