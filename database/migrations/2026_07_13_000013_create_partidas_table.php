<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->unsignedSmallInteger('numero_partida');
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2)->unsigned()->default(0.00);
            $table->string('unidad', 50)->nullable();
            $table->decimal('precio_unitario', 15, 2)->unsigned()->default(0.00);
            $table->decimal('importe', 15, 2)->unsigned()->default(0.00);
            $table->decimal('costo_hora', 10, 2)->default(0.00);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
