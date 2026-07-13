<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_items', function (Blueprint $table) {
            $table->id();
            $table->enum('categoria', ['herramienta', 'material', 'epp', 'equipo']);
            $table->string('codigo', 100)->nullable();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->integer('cantidad')->default(0);
            $table->string('unidad', 50)->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->enum('estado', ['disponible', 'prestado', 'danado', 'baja'])->default('disponible');
            $table->json('atributos_extra')->nullable();
            $table->unsignedBigInteger('usuario_registro_id')->nullable();
            $table->foreign('usuario_registro_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_items');
    }
};
