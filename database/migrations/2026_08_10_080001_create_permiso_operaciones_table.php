<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRMS de RBAC0: el par (objeto, operación) que un objeto declara
     * como aplicable. Un rol solo puede recibir una operación sobre un
     * objeto si ese par existe aquí primero — evita, por ejemplo, que
     * alguien otorgue "aprobar" sobre `ingenierias.plantas`, que no
     * tiene sentido de negocio para ese objeto.
     */
    public function up(): void
    {
        Schema::create('permiso_operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnDelete();
            $table->unique(['permiso_id', 'operacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permiso_operaciones');
    }
};
