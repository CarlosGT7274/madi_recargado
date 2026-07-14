<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aqui vive el UNICO bitmask del sistema:
     * READ = 1, CREATE = 2, UPDATE = 4, DELETE = 8, ALL = 15
     * No hay tabla de "permisos" con nombres; el permiso es este entero por (rol, recurso).
     */
    public function up(): void
    {
        Schema::create('role_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->unsignedTinyInteger('permisos')->default(0); // 0-15
            $table->timestamps();

            $table->unique(['role_id', 'recurso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_recurso');
    }
};
