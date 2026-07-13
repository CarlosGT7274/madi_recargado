<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planeacion_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planeacion_id');
            $table->foreign('planeacion_id')->references('id')->on('planeaciones')->onDelete('cascade');
            $table->unsignedBigInteger('actividad_id');
            $table->foreign('actividad_id')->references('id')->on('planeacion_actividades')->onDelete('cascade');
            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']);
            $table->enum('estado', ['asignado', 'en_progreso', 'completado', 'cancelado'])->default('asignado');
            $table->decimal('horas_trabajadas', 5, 2)->default(0.00);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planeacion_asignaciones');
    }
};
