<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->string('codigo', 150);
            $table->string('concepto', 500);
            $table->string('unidad', 50);
            $table->enum('categoria', ['materiales', 'mano_obra', 'maquinaria'])->default('materiales');
            $table->decimal('cantidad_presupuestada', 15, 2)->unsigned()->default(0.00);
            $table->decimal('cantidad_requisitada', 15, 2)->unsigned()->default(0.00);
            $table->decimal('precio', 15, 2)->default(0.00);
            $table->decimal('importe', 15, 2)->nullable();
            $table->decimal('valor_total', 15, 2)->default(0.00);
            $table->enum('estatus', ['pendiente', 'requisitado', 'comprado', 'entregado'])->default('pendiente');
            $table->boolean('activo')->default(1);
            $table->unsignedBigInteger('usuario_carga_id')->nullable();
            $table->foreign('usuario_carga_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_carga')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
