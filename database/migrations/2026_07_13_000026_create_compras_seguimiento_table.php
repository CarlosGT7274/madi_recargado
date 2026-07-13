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
            $table->unsignedBigInteger('compra_orden_id');
            $table->foreign('compra_orden_id')->references('id')->on('compras_ordenes')->onDelete('cascade');
            $table->unsignedBigInteger('requisicion_material_id');
            $table->foreign('requisicion_material_id')->references('id')->on('requisicion_materiales')->onDelete('cascade');
            $table->enum('via', ['surtir', 'comprar', 'Decisión de Compras: surtir de existencia o comprar nuevo'])->nullable();
            $table->integer('cantidad_aprobada')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['requisicion_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_seguimiento');
    }
};
