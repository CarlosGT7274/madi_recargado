<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('levantamiento_id');
            $table->foreign('levantamiento_id')->references('id')->on('levantamientos')->onDelete('cascade');
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo', 'completado', 'cancelado'])->default('activo');
            $table->string('estado_revision', 255)->nullable();
            $table->boolean('bloqueado')->default(0);
            $table->string('motivo_bloqueo', 500)->nullable();
            $table->timestamp('fecha_bloqueo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
