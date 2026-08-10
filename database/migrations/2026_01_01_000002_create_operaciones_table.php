<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de OPERACIONES del modelo Core RBAC (ANSI/INCITS 359).
     *
     * Una operación es una acción del sistema (`leer`, `crear`, `aprobar`,
     * `rechazar`, `firmar`, ...). Ya NO existe el bitmask CRUD: cada
     * operación es una fila explícita, y el catálogo crece con operaciones
     * reales del negocio sin tocar el esquema.
     *
     * `clave` es el identificador estable usado por rutas, middleware y
     * frontend (p. ej. `aprobar`); `nombre` es la etiqueta mostrada.
     */
    public function up(): void
    {
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones');
    }
};
