<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantas', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 100);
            $table->string('nombre', 255);
            $table->string('direccion', 500)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(1);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['folio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantas');
    }
};
