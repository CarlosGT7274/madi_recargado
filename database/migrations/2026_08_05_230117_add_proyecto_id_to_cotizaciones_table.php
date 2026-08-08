<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `cotizaciones` nació con `proyecto_id` (proyecto como raíz) pero sin
     * `levantamiento_id`. Se agrega aquí, nullable, porque el flujo de
     * Proyecto directo (tipo 'chico') no pasa por Levantamiento —
     * ver Cotizacion::esDeProyectoDirecto().
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('levantamiento_id')->nullable()->after('id')
                ->constrained('levantamientos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['levantamiento_id']);
            $table->dropColumn('levantamiento_id');
        });
    }
};
