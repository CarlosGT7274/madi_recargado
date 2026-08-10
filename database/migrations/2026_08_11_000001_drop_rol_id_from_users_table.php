<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La relación usuario→rol ahora vive en la tabla pivote `role_user`
     * (muchos a muchos). La columna `rol_id` de un solo rol deja de tener
     * sentido en el modelo Core RBAC y se elimina.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('id')
                ->constrained('roles')->nullOnDelete();
        });
    }
};
