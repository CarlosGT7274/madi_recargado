<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('mensaje', 255);
            $table->string('destino_area', 100);
            $table->string('modulo', 100);
            $table->boolean('leida')->default(0);
            $table->string('tipo_entidad', 50)->nullable();
            $table->unsignedInteger('entidad_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('es_general')->default(1);
            $table->timestamp('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
