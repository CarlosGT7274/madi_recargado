<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de operaciones (OPS en RBAC0 / ANSI-INCITS 359). CRUD vive
     * aquí como cuatro filas normales (ver/crear/actualizar/eliminar),
     * sin tratamiento especial — el catálogo admite cualquier operación
     * de negocio adicional (enviar, aprobar, rechazar, archivar, firmar...).
     */
    public function up(): void
    {
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones');
    }
};
