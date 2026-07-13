<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('periodo_nomina_id');
            $table->foreign('periodo_nomina_id')->references('id')->on('periodos_nomina')->onDelete('cascade');
            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('dias_trabajados')->default(0);
            $table->decimal('horas_normales', 10, 2)->default(0.00);
            $table->decimal('horas_extra', 10, 2)->default(0.00);
            $table->decimal('precio_hora', 10, 2);
            $table->decimal('pago_horas_normales', 12, 2)->default(0.00);
            $table->decimal('pago_horas_extra', 12, 2)->default(0.00);
            $table->decimal('total_deducciones', 12, 2)->default(0.00);
            $table->decimal('neto_pagar', 12, 2)->default(0.00);
            $table->enum('estado', ['calculada', 'pagada', 'cancelada'])->default('calculada');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'cheque'])->nullable();
            $table->string('referencia_pago', 100)->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->unsignedBigInteger('calculado_por_id');
            $table->foreign('calculado_por_id')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('pagado_por_id')->nullable();
            $table->foreign('pagado_por_id')->references('id')->on('users')->onDelete('set null');
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['periodo_nomina_id', 'empleado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominas');
    }
};
