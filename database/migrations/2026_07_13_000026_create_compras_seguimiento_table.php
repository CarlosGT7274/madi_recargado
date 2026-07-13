<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_orden_id')->constrained('compras_ordenes')->cascadeOnDelete();
            $table->foreignId('requisicion_material_id')->constrained('requisicion_materiales')->cascadeOnDelete();
            $table->enum('via', ['surtir', 'comprar'])->nullable();
            $table->integer('cantidad_aprobada')->nullable();
            $table->timestamps();
            $table->unique(['requisicion_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_seguimiento');
    }
};
