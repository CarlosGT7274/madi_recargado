<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * User Assignment del Core RBAC: un usuario puede tener UNO O VARIOS
     * roles. Sustituye a la columna `users.rol_id` (1 usuario → 1 rol),
     * que se elimina en la migración siguiente.
     *
     * Los permisos efectivos de un usuario son la UNIÓN de los permisos
     * de todos sus roles. Todavía NO hay "rol activo" ni jerarquías de
     * roles (eso sería RBAC1); aquí solo queda listo el núcleo.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
