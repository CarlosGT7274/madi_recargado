<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planeacion_actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planeacion_id');
            $table->foreign('planeacion_id')->references('id')->on('planeaciones')->onDelete('cascade');
            $table->unsignedBigInteger('partida_id')->nullable();
            $table->foreign('partida_id')->references('id')->on('partidas')->onDelete('set null');
            $table->string('codigo', 50);
            $table->string('nombre', 500);
            $table->enum('dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']);
            $table->text('notas')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planeacion_actividades');
    }
};
