<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tiene_insumos y tiene_orden_compra eran copias manuales de hechos que
     * ya existen en `insumos.cotizacion_id` y `compras_ordenes.cotizacion_id`.
     * Se reemplazan por Cotizacion::tieneInsumos() / tieneOrdenAprobada().
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['tiene_insumos', 'tiene_orden_compra']);
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->boolean('tiene_insumos')->default(0);
            $table->boolean('tiene_orden_compra')->default(0);
        });
    }
};
