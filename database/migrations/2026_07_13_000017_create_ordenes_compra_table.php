<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->string('folio', 100);
            $table->string('proveedor', 255)->nullable();
            $table->date('fecha_orden');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();
            $table->decimal('subtotal', 15, 2)->unsigned()->default(0.00);
            $table->decimal('iva', 15, 2)->unsigned()->default(0.00);
            $table->decimal('total', 15, 2)->unsigned()->default(0.00);
            $table->enum('estado', ['pendiente', 'enviada', 'recibida', 'cancelada'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['folio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
