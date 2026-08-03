<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * bloqueado/motivo_bloqueo/fecha_bloqueo eran un snapshot manual de si
     * alguna cotización del proyecto ya está completada (insumos + OC
     * aprobada). Se reemplaza por Proyecto::estaCompletado().
     */
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['bloqueado', 'motivo_bloqueo', 'fecha_bloqueo']);
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->boolean('bloqueado')->default(0);
            $table->string('motivo_bloqueo', 500)->nullable();
            $table->timestamp('fecha_bloqueo')->nullable();
        });
    }
};
