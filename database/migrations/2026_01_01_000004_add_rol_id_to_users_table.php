<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esta migración SOLO agrega una columna a `users`. No toca
     * password_reset_tokens, sessions, ni 2FA/verificación de correo —
     * eso sigue siendo responsabilidad de Laravel/Fortify.
     *
     * OJO: es `rol_id` (español), no `role_id`. Todo el resto del sistema
     * usa nombres en español para las FK (usuario_id, proyecto_id, etc.),
     * y el Role::rol() / User::rol() apuntan a esta columna. La versión
     * anterior traía `role_id` y tronaba con "Unknown column".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('id')
                ->constrained('roles')->nullOnDelete();
            $table->decimal('precio_hora_general', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
            $table->dropColumn('precio_hora_general');
        });
    }
};
