<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas_precios_hora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partida_id');
            $table->foreign('partida_id')->references('id')->on('partidas')->onDelete('cascade');
            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('precio_hora', 10, 2)->default(0.00);
            $table->unsignedBigInteger('creado_por_id')->nullable();
            $table->foreign('creado_por_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas_precios_hora');
    }
};
