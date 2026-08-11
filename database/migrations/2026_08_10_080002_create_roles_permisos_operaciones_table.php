<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PA de RBAC0 (Permission Assignment): qué (objeto, operación) tiene
     * asignado cada rol. Reemplaza a roles_permisos.permisos (bitmask).
     */
    public function up(): void
    {
        Schema::create('roles_permisos_operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_operacion_id')->constrained('permiso_operaciones')->cascadeOnDelete();
            $table->unique(['rol_id', 'permiso_operacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permisos_operaciones');
    }
};
