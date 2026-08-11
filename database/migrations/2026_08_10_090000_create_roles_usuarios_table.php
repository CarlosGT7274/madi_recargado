<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.rol_id (singular) se reemplaza por esta tabla pivote —
     * RBAC0 permite N roles por usuario. El backfill vive en la misma
     * migración porque es la operación que justifica dropear la columna:
     * separarlo en dos migraciones no aporta nada, solo arriesga que
     * alguien corra la segunda sin la primera.
     */
    public function up(): void
    {
        Schema::create('roles_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['rol_id', 'usuario_id']);
        });

        DB::table('users')->whereNotNull('rol_id')->orderBy('id')->cursor()->each(function ($usuario) {
            DB::table('roles_usuarios')->insert([
                'rol_id' => $usuario->rol_id,
                'usuario_id' => $usuario->id,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
        });

        DB::table('roles_usuarios')
            ->orderBy('usuario_id')
            ->get()
            ->groupBy('usuario_id')
            ->each(function ($filas, $usuarioId) {
                DB::table('users')->where('id', $usuarioId)->update(['rol_id' => $filas->first()->rol_id]);
            });

        Schema::dropIfExists('roles_usuarios');
    }
};
