<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planeaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('semana');
            $table->unsignedInteger('anio');
            $table->unsignedBigInteger('planta_id');
            $table->foreign('planta_id')->references('id')->on('plantas')->onDelete('cascade');
            $table->unsignedBigInteger('proyecto_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
            $table->unsignedBigInteger('usuario_id');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('estado', ['borrador', 'enviada', 'aprobada', 'rechazada'])->default('borrador');
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamp('fecha_rechazo')->nullable();
            $table->unsignedBigInteger('aprobador_id')->nullable();
            $table->foreign('aprobador_id')->references('id')->on('users')->onDelete('set null');
            $table->text('comentarios_aprobacion')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['planta_id', 'proyecto_id', 'semana', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planeaciones');
    }
};
