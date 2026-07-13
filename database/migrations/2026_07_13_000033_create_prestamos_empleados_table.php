<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_empleados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('monto_total', 12, 2);
            $table->decimal('monto_quincenal', 12, 2);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->integer('numero_pagos_total');
            $table->integer('numero_pagos_realizados')->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_ultimo_pago')->nullable();
            $table->enum('estado', ['activo', 'liquidado', 'cancelado'])->default('activo');
            $table->text('descripcion')->nullable();
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('creado_por_id')->nullable();
            $table->foreign('creado_por_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_empleados');
    }
};
