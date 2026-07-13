<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisiciones', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 255);
            $table->unsignedBigInteger('proyecto_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('restrict');
            $table->unsignedBigInteger('cotizacion_id')->nullable();
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('set null');
            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('users')->onDelete('restrict');
            $table->enum('estado', ['borrador', 'enviada', 'aprobada', 'rechazada', 'cancelada'])->default('borrador');
            $table->string('origen', 100);
            $table->string('observaciones', 255)->nullable();
            $table->boolean('enviada_a_compras')->default(0);
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->unique(['folio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisiciones');
    }
};
