<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asignación de PERMISOS a ROLES (Permission Assignment del Core RBAC).
     *
     * Cada fila es un permiso concreto otorgado a un rol, expresado como
     * la tripleta del modelo estándar:
     *
     *     rol + objeto (permiso_id) + operación (operacion_id)
     *
     * Se acabó el `tinyint` de bits que había que interpretar con bitwise.
     * Un rol "puede aprobar Planeación" es literalmente una fila
     * (rol=Supervisor, objeto=Planeación, operación=aprobar).
     */
    public function up(): void
    {
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnDelete();

            $table->unique(['rol_id', 'permiso_id', 'operacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};
