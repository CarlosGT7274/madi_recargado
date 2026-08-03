<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropForeign(['levantamiento_id']);
            $table->dropColumn('levantamiento_id');

            $table->unsignedBigInteger('planta_id')->after('id');
            $table->foreign('planta_id')->references('id')->on('plantas')->onDelete('cascade');

            $table->string('folio', 100)->after('planta_id');
            $table->unique('folio');

            // Determina qué rama de UI/flujo sigue: con Levantamiento o directo a Actividades.
            $table->enum('tipo', ['grande', 'chico'])->default('grande')->after('folio');
        });

        Schema::table('levantamientos', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id')->nullable()->after('planta_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('levantamientos', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->dropColumn('proyecto_id');
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropForeign(['planta_id']);
            $table->dropColumn(['planta_id', 'folio', 'tipo']);
            $table->unsignedBigInteger('levantamiento_id');
            $table->foreign('levantamiento_id')->references('id')->on('levantamientos')->onDelete('cascade');
        });
    }
};
