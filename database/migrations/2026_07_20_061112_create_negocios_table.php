<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "Donde Mechas", "Bar El Tapitas"
            $table->string('slug')->unique(); // Ej: "donde-mechas" (para URLs)
            $table->string('nit_rut')->nullable();
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            
            // Control de Suscripción / Plan SaaS
            $table->enum('plan', ['basico', 'pro', 'enterprise'])->default('basico');
            $table->enum('estado_suscripcion', ['activo', 'suspendido', 'prueba'])->default('prueba');
            $table->dateTime('fecha_vencimiento')->nullable(); // Control exacto de corte de servicio
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
