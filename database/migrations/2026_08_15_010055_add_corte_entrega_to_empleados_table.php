<?php

// database/migrations/2026_08_15_000001_add_corte_entrega_to_empleados_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->enum('corte_dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])
                ->nullable()->after('activo');
            $table->time('corte_hora')->nullable()->after('corte_dia_semana');
            $table->enum('corte_semana_relativa', ['actual', 'anterior'])->default('anterior')->after('corte_hora');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['corte_dia_semana', 'corte_hora', 'corte_semana_relativa']);
        });
    }
};
