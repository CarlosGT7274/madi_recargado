<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * roles_permisos (rol_id, permiso_id, permisos:bitmask) queda
     * reemplazada por roles_permisos_operaciones. Se elimina en vez de
     * dejarla muerta junto a la tabla nueva.
     */
    public function up(): void
    {
        Schema::dropIfExists('roles_permisos');
    }

    public function down(): void
    {
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->unsignedTinyInteger('permisos')->default(0);
            $table->unique(['rol_id', 'permiso_id']);
        });
    }
};
