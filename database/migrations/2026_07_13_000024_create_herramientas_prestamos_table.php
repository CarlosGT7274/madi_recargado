<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('herramientas_prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('inventario_id')->constrained('inventario')->cascadeOnDelete();
            $table->foreignId('requisicion_material_id')->nullable()->constrained('requisicion_materiales')->nullOnDelete();
            $table->integer('cantidad');
            $table->enum('condicion', ['nueva', 'buena', 'regular', 'dañada'])->default('buena');
            $table->enum('estado_asignacion', ['prestado', 'devuelto', 'perdido', 'dañado'])->default('prestado');
            $table->string('descripcion', 255)->nullable();
            $table->string('ubicacion', 255);
            $table->string('observaciones', 255)->nullable();
            $table->date('fecha_entrega');
            $table->date('fecha_devolucion')->nullable();
            $table->timestamp('ultima_actualizacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herramientas_prestamos');
    }
};
