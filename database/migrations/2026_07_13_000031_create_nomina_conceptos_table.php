<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_conceptos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nomina_id');
            $table->foreign('nomina_id')->references('id')->on('nominas')->onDelete('cascade');
            $table->enum('concepto', ['isr', 'imss', 'infonavit', 'prestamo', 'faltas', 'bono_puntualidad', 'prima_vacacional', 'horas_extra', 'otro']);
            $table->decimal('monto', 12, 2);
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_conceptos');
    }
};
