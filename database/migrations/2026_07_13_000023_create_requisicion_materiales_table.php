<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisicion_materiales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisicion_id');
            $table->foreign('requisicion_id')->references('id')->on('requisiciones')->onDelete('cascade');
            $table->unsignedBigInteger('insumo_id')->nullable();
            $table->foreign('insumo_id')->references('id')->on('insumos')->onDelete('set null');
            $table->string('material', 255);
            $table->string('descripcion', 255);
            $table->integer('cantidad');
            $table->string('unidad_medida', 255);
            $table->enum('urgencia', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->enum('estado_material', ['disponible', 'agotado', 'dañado'])->nullable();
            $table->boolean('material_distinto_a_autorizado')->default(0);
            $table->text('nota_material_distinto')->nullable();
            $table->date('fecha_llegada')->nullable();
            $table->text('observacion')->nullable();
            $table->integer('inventario_actual')->nullable();
            $table->integer('cantidad_entregada')->default(0);
            $table->integer('sugerencia_compra')->nullable();
            $table->decimal('importe', 15, 2)->default(0.00);
            $table->boolean('enviado_a_compras')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisicion_materiales');
    }
};
