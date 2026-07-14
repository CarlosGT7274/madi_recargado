<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esta migracion SOLO agrega una columna a la tabla `users` que ya trae el starter kit.
     * No toca password_reset_tokens, sessions, ni nada relacionado a 2FA/verificacion de correo:
     * eso sigue siendo 100% responsabilidad de Laravel/Fortify.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')
                ->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
