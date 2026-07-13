<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_ordenes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orden', 100)->nullable();
            $table->string('proveedor', 255)->nullable();
            $table->string('proveedor_rfc', 20)->nullable();
            $table->enum('estatus_compra', ['pendiente', 'en_cotizacion', 'aprobado', 'rechazado', 'orden_generada', 'en_transito', 'entregado'])->default('pendiente');
            $table->timestamp('fecha_solicitud_compra')->useCurrent();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->date('fecha_estimada_entrega')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('usuario_registro_id')->nullable();
            $table->foreign('usuario_registro_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('usuario_modificacion_id')->nullable();
            $table->foreign('usuario_modificacion_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_ordenes');
    }
};
