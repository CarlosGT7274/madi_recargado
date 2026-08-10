<?php

// database/migrations/2026_08_10_000002_repoint_planeacion_asignaciones_to_partidas.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ya no existe planeacion_actividades como intermediario — una
     * asignación apunta directo a la partida que se está trabajando.
     */
    public function up(): void
    {
        Schema::table('planeacion_asignaciones', function (Blueprint $table) {
            $table->renameColumn('actividad_id', 'partida_id');
        });

        Schema::table('planeacion_asignaciones', function (Blueprint $table) {
            $table->foreign('partida_id')->references('id')->on('partidas')->onDelete('cascade');
            $table->decimal('horas_extra', 5, 2)->default(0.00)->after('horas_trabajadas');
        });
    }

    public function down(): void
    {
        Schema::table('planeacion_asignaciones', function (Blueprint $table) {
            $table->dropForeign(['partida_id']);
            $table->dropColumn('horas_extra');
            $table->renameColumn('partida_id', 'actividad_id');
        });
    }
};
