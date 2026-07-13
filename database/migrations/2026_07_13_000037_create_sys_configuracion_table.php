<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100);
            $table->text('valor')->nullable();
            $table->enum('tipo', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->string('categoria', 50)->default('general');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('editable')->default(1);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_configuracion');
    }
};
