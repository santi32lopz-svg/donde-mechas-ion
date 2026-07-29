<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices compuestos para las consultas de la terminal de venta.
 *
 * constrained() ya dejó índices sueltos sobre negocio_id y categoria_id, pero
 * la cuadrícula del POS siempre filtra por negocio_id junto con disponible o
 * categoria_id, y ahí un índice compuesto es mucho más selectivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->index(['negocio_id', 'disponible'], 'productos_negocio_disponible_idx');
            $table->index(['negocio_id', 'categoria_id'], 'productos_negocio_categoria_idx');
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->index(['negocio_id', 'activo'], 'categorias_negocio_activo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_negocio_disponible_idx');
            $table->dropIndex('productos_negocio_categoria_idx');
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropIndex('categorias_negocio_activo_idx');
        });
    }
};
