<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('partida_id')->nullable()->constrained('partidas')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_partida');
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2)->unsigned()->default(0.00);
            $table->string('unidad', 50)->nullable();
            $table->decimal('precio_unitario', 15, 2)->unsigned()->default(0.00);
            $table->decimal('importe', 15, 2)->unsigned()->default(0.00);
            $table->decimal('costo_hora', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
