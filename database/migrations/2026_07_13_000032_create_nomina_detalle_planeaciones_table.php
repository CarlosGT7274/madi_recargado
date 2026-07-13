<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_detalle_planeaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nomina_id');
            $table->foreign('nomina_id')->references('id')->on('nominas')->onDelete('cascade');
            $table->unsignedBigInteger('planeacion_id');
            $table->foreign('planeacion_id')->references('id')->on('planeaciones')->onDelete('cascade');
            $table->unsignedBigInteger('asignacion_id');
            $table->foreign('asignacion_id')->references('id')->on('planeacion_asignaciones')->onDelete('cascade');
            $table->decimal('horas_trabajadas', 10, 2);
            $table->decimal('precio_hora', 10, 2);
            $table->decimal('monto_calculado', 12, 2);
            $table->unsignedInteger('planta_id')->nullable();
            $table->unsignedInteger('proyecto_id')->nullable();
            $table->unsignedInteger('cotizacion_id')->nullable();
            $table->string('dia_semana', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_detalle_planeaciones');
    }
};
