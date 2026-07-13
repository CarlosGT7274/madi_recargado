<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levantamientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planta_id');
            $table->foreign('planta_id')->references('id')->on('plantas')->onDelete('cascade');
            $table->string('folio', 100);
            $table->string('nombre', 255);
            $table->string('cliente', 255);
            $table->string('obra', 255)->nullable();
            $table->string('solicitante', 255)->nullable();
            $table->date('fecha_solicitud')->nullable();
            $table->string('usuario_requiriente', 255)->nullable();
            $table->string('correo_usuario', 255)->nullable();
            $table->string('area_trabajo', 255)->nullable();
            $table->text('titulo_cotizacion')->nullable();
            $table->boolean('trabajos_alturas_certificado')->default(0);
            $table->text('trabajos_alturas_notas')->nullable();
            $table->boolean('espacios_confinados_aplica')->default(0);
            $table->boolean('espacios_confinados_certificado')->default(0);
            $table->text('espacios_confinados_notas')->nullable();
            $table->boolean('corte_soldadura_aplica')->default(0);
            $table->boolean('corte_soldadura_certificado')->default(0);
            $table->text('corte_soldadura_notas')->nullable();
            $table->boolean('izaje_aplica')->default(0);
            $table->boolean('izaje_certificado')->default(0);
            $table->text('izaje_notas')->nullable();
            $table->boolean('apertura_lineas_aplica')->default(0);
            $table->boolean('apertura_lineas_certificado')->default(0);
            $table->text('apertura_lineas_notas')->nullable();
            $table->boolean('excavacion_aplica')->default(0);
            $table->boolean('excavacion_certificado')->default(0);
            $table->text('excavacion_notas')->nullable();
            $table->text('notas_maquinaria')->nullable();
            $table->enum('estatus_admin', ['recibida', 'levantamiento_proceso', 'levantamiento_listo', 'cotizando', 'revision_residente', 'correcciones', 'lista_enviar', 'enviada', 'ganada', 'perdida', 'cancelada'])->default('recibida');
            $table->enum('medio_solicitud', ['portal', 'correo', 'whatsapp', 'telefono'])->nullable();
            $table->enum('prioridad', ['urgente', 'normal', 'grande_compleja'])->default('normal');
            $table->text('notas_admin')->nullable();
            $table->date('fecha_levantamiento_programada')->nullable();
            $table->date('fecha_envio_cotizacion_programada')->nullable();
            $table->date('fecha_cotizacion_enviada')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['folio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levantamientos');
    }
};
