<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
            $table->string('folio', 100);
            $table->date('fecha');
            $table->string('para', 255)->nullable();
            $table->string('cliente', 255)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('obra', 255)->nullable();
            $table->string('vendedor', 255)->nullable();
            $table->string('proveedor', 255)->nullable();
            $table->string('correo_vendedor', 255)->nullable();
            $table->decimal('subtotal', 15, 2)->unsigned()->default(0.00);
            $table->decimal('iva', 15, 2)->unsigned()->default(0.00);
            $table->decimal('total', 15, 2)->unsigned()->default(0.00);
            $table->decimal('costo_hora_total', 10, 2)->default(0.00);
            $table->text('importe_letra')->nullable();
            $table->string('moneda', 50)->default('PESOS MXN');
            $table->string('tiempo_entrega', 100)->nullable();
            $table->string('dias_credito', 50)->nullable();
            $table->string('vigencia_cotizacion', 100)->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['borrador', 'enviada', 'aprobada', 'rechazada'])->default('borrador');
            $table->date('fecha_aprobacion')->nullable();
            $table->boolean('tiene_insumos')->default(0);
            $table->boolean('tiene_orden_compra')->default(0);
            $table->boolean('tiene_partidas')->default(0);
            $table->decimal('presupuesto_consumido', 15, 2)->default(0.00);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['folio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
