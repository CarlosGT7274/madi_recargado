<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->string('accion', 255);
            $table->string('descripcion', 500);
            $table->string('ip', 45);
            $table->timestamp('fecha_hora')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
