<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * empleado = entidad de negocio, user = autenticación. Estas seis
     * tablas usaban users.id para "empleado_id" porque el módulo de
     * nómina/almacén todavía no está implementado (solo modelos y
     * migraciones), así que no hay lógica que romper con el repunteo.
     */
    private const TABLAS = [
        'nominas',
        'prestamos_empleados',
        'herramientas_prestamos',
        'partidas_precios_hora',
        'planeacion_asignaciones',
        'requisiciones',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropForeign(["{$tabla}_empleado_id_foreign"] === null ? [] : ['empleado_id']);
            });
        }

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreign('empleado_id')->references('id')->on('empleados')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['empleado_id']);
            });
        }

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreign('empleado_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
