<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_pagos_prestamos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_id');
            $table->foreign('prestamo_id')->references('id')->on('prestamos_empleados')->onDelete('cascade');
            $table->unsignedBigInteger('nomina_id');
            $table->foreign('nomina_id')->references('id')->on('nominas')->onDelete('cascade');
            $table->decimal('monto_pagado', 12, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_nuevo', 12, 2);
            $table->timestamp('fecha_pago')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_pagos_prestamos');
    }
};
