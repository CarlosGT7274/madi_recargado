<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 50);
            $table->string('tipo', 50);
            $table->string('prefijo', 20);
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->timestamp('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['modulo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
