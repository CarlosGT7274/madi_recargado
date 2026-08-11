<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de operaciones RBAC (ANSI/INCITS 359): acciones
     * ejecutables sobre un objeto, no limitadas a CRUD. `bit` es el valor
     * usado en el bitmask de roles_permisos.permisos — fijo una vez usado
     * en producción, igual que READ/CREATE/UPDATE/DELETE hoy.
     *
     * permiso_operaciones declara, por objeto, qué operaciones aplican.
     * Sin fila aquí = la operación no tiene sentido para ese objeto.
     */
    public function up(): void
    {
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('bit')->unique();
            $table->boolean('basica')->default(false);
            $table->unsignedTinyInteger('orden')->default(0);
        });

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
        Schema::dropIfExists('operaciones');
    }
};
