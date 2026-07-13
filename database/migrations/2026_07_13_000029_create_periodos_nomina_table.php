<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_nomina', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['semanal', 'quincenal', 'mensual'])->default('semanal');
            $table->integer('numero_semana');
            $table->integer('anio');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_pago');
            $table->enum('estado', ['abierto', 'calculado', 'pagado', 'cerrado'])->default('abierto');
            $table->integer('total_empleados')->default(0);
            $table->decimal('total_horas', 10, 2)->default(0.00);
            $table->decimal('total_percepciones', 12, 2)->default(0.00);
            $table->decimal('total_deducciones', 12, 2)->default(0.00);
            $table->decimal('neto_total', 12, 2)->default(0.00);
            $table->unsignedBigInteger('creado_por_id')->nullable();
            $table->foreign('creado_por_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('calculado_por_id')->nullable();
            $table->foreign('calculado_por_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('pagado_por_id')->nullable();
            $table->foreign('pagado_por_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_calculo')->nullable();
            $table->timestamp('fecha_pago_real')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['tipo', 'numero_semana', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_nomina');
    }
};
