<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El "orden de compra" que hoy se sube en Cotizaciones nunca fue una
     * orden de compra real: es un PDF de autorización del cliente. Se
     * resuelve con `archivos` (Cotizacion ya tiene HasArchivos) y con
     * estatus_compra movida directo a `cotizaciones`.
     *
     * compras_ordenes, compras_seguimiento y compras_tracking se eliminan
     * por completo. El módulo real de Compras (que nace de una
     * RequisicionMaterial sin stock en inventario, no de una Cotizacion)
     * todavía no se ha construido y se diseñará desde cero cuando exista
     * Requisiciones + Inventario funcionando.
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->enum('estatus_compra', ['pendiente', 'en_cotizacion', 'aprobado', 'rechazado'])
                ->default('pendiente')->after('estado');
            $table->foreignId('aprobador_compra_id')->nullable()->after('estatus_compra')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion_compra')->nullable()->after('aprobador_compra_id');
        });

        Schema::dropIfExists('compras_seguimiento');
        Schema::dropIfExists('compras_tracking');
        Schema::dropIfExists('compras_ordenes');
    }

    public function down(): void
    {
        Schema::create('compras_ordenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $table->string('numero_orden', 100)->nullable();
            $table->string('proveedor', 255)->nullable();
            $table->string('proveedor_rfc', 20)->nullable();
            $table->enum('estatus_compra', [
                'pendiente', 'en_cotizacion', 'aprobado', 'rechazado',
                'orden_generada', 'en_transito', 'entregado',
            ])->default('pendiente');
            $table->timestamp('fecha_solicitud_compra')->useCurrent();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->date('fecha_estimada_entrega')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_registro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_modificacion_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('compras_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_orden_id')->constrained('compras_ordenes')->cascadeOnDelete();
            $table->foreignId('requisicion_material_id')->constrained('requisicion_materiales')->cascadeOnDelete();
            $table->enum('via', ['surtir', 'comprar'])->nullable();
            $table->integer('cantidad_aprobada')->nullable();
            $table->timestamps();
            $table->unique(['requisicion_material_id']);
        });

        Schema::create('compras_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_orden_id')->constrained('compras_ordenes')->cascadeOnDelete();
            $table->string('estado', 100);
            $table->text('observacion')->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('fecha')->useCurrent();
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['aprobador_compra_id']);
            $table->dropColumn(['estatus_compra', 'aprobador_compra_id', 'fecha_aprobacion_compra']);
        });
    }
};
