<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Declara QUÉ operaciones son válidas para cada objeto protegible.
     *
     * En términos de RBAC: define el universo de "permisos" asignables
     * (objeto + operación). Sin una fila aquí, no tiene sentido ofrecer
     * `aprobar` sobre un objeto donde esa operación no aplica: la matriz
     * de permisos de un rol solo puede mostrar/otorgar las combinaciones
     * declaradas en esta tabla.
     *
     * El nombre `objeto_operacion` es deliberado: `permisos` es nuestro
     * árbol de OBJETOS, y esta tabla los cruza con OPERACIONES.
     */
    public function up(): void
    {
        Schema::create('objeto_operacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnDelete();

            $table->unique(['permiso_id', 'operacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objeto_operacion');
    }
};
