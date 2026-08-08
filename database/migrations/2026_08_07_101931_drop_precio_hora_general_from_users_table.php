<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * precio_hora_general se mueve a empleados.precio_hora_general
     * (ver create_empleados_table). users queda solo para auth.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('precio_hora_general');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('precio_hora_general', 10, 2)->nullable();
        });
    }
};
