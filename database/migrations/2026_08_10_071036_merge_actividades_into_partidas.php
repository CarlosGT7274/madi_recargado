<?php

// database/migrations/2026_08_10_000001_merge_actividades_into_partidas.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fusiona la vieja `planeacion_actividades` (árbol manual de Proyecto
     * directo) dentro de `partidas`, que pasa a ser la ÚNICA fuente de
     * verdad de "actividades", vengan de una cotización o sean manuales.
     *
     * cotizacion_id se vuelve nullable: una partida manual no tiene
     * cotización. proyecto_id se agrega: se llena SIEMPRE (tanto en
     * partidas manuales como en partidas de cotización, denormalizado
     * a propósito para no tener que subir por cotizacion->proyecto_id
     * en cada query).
     */
    public function up(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->unsignedBigInteger('cotizacion_id')->nullable()->change();
            $table->unsignedBigInteger('proyecto_id')->nullable()->after('cotizacion_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
            $table->text('notas')->nullable()->after('descripcion');
        });

        // Backfill: partidas que ya cuelgan de una cotización heredan el
        // proyecto_id de esa cotización (la cotización siempre lo tiene,
        // tanto en flujo grande como directo).
        DB::statement('
            UPDATE partidas
            INNER JOIN cotizaciones ON cotizaciones.id = partidas.cotizacion_id
            SET partidas.proyecto_id = cotizaciones.proyecto_id
            WHERE partidas.cotizacion_id IS NOT NULL
        ');

        if (Schema::hasTable('planeacion_actividades')) {
            if (Schema::hasTable('planeacion_asignaciones')) {
                Schema::table('planeacion_asignaciones', function (Blueprint $table) {
                    $table->dropForeign(['actividad_id']);
                });
            }

            $filas = DB::table('planeacion_actividades')
                ->whereNotNull('proyecto_id')
                ->orderBy('id')
                ->get();

            $mapaIds = [];

            foreach ($filas as $fila) {
                $nuevoId = DB::table('partidas')->insertGetId([
                    'proyecto_id' => $fila->proyecto_id,
                    'cotizacion_id' => null,
                    'partida_id' => $fila->parent_id ? ($mapaIds[$fila->parent_id] ?? null) : null,
                    'numero_partida' => 0,
                    'descripcion' => $fila->nombre,
                    'notas' => $fila->notas,
                    'cantidad' => 0,
                    'unidad' => null,
                    'precio_unitario' => 0,
                    'importe' => 0,
                    'costo_hora' => 0,
                    'created_at' => $fila->fecha_creacion,
                    'updated_at' => $fila->fecha_modificacion,
                ]);

                $mapaIds[$fila->id] = $nuevoId;
            }

            Schema::drop('planeacion_actividades');
        }

        // Si dejaste corridas las 6 migraciones anteriores, esta tabla ya
        // se llamaba proyecto_actividades — cúbrela también por si acaso.
        if (Schema::hasTable('proyecto_actividades')) {
            Schema::drop('proyecto_actividades');
        }
    }

    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->dropColumn(['proyecto_id', 'notas']);
            $table->unsignedBigInteger('cotizacion_id')->nullable(false)->change();
        });
    }
};
