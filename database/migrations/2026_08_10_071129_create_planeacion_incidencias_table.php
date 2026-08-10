<?php

// database/migrations/2026_08_10_000003_create_planeacion_incidencias_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planeacion_incidencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asignacion_id');
            $table->foreign('asignacion_id')->references('id')->on('planeacion_asignaciones')->onDelete('cascade');
            $table->enum('tipo', ['falta', 'vacaciones', 'cambio_dia', 'movimiento', 'enfermedad', 'horas_extra', 'otro']);
            $table->enum('dia_anterior', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])->nullable();
            $table->enum('dia_nuevo', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])->nullable();
            $table->decimal('horas_extra', 5, 2)->nullable();
            $table->date('fecha')->nullable();
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planeacion_incidencias');
    }
};
