<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aquí vive el ÚNICO bitmask del sistema:
     * READ = 1, CREATE = 2, UPDATE = 4, DELETE = 8, ALL = 15
     * No hay nombres de permisos hardcodeados; el permiso es este entero
     * por (rol, recurso). La jerarquía se resuelve en PHP (Role::tienePermiso)
     * subiendo por `recursos.padre_id`, no aquí.
     */
    public function up(): void
    {
        Schema::create('roles_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->unsignedTinyInteger('permisos')->default(0);
            $table->timestamps();

            $table->unique(['rol_id', 'recurso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_recursos');
    }
};
