<?php

// database/migrations/2026_08_10_000004_add_pivots_firma_and_nomina_flags.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planta_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planta_id')->constrained('plantas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['planta_id', 'usuario_id']);
        });

        Schema::create('proyecto_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['proyecto_id', 'usuario_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('firma_url', 500)->nullable()->after('rol_id');
        });

        Schema::table('planeaciones', function (Blueprint $table) {
            $table->boolean('reportada_nomina')->default(false)->after('estado');
            $table->timestamp('fecha_reporte_nomina')->nullable()->after('reportada_nomina');
        });
    }

    public function down(): void
    {
        Schema::table('planeaciones', function (Blueprint $table) {
            $table->dropColumn(['reportada_nomina', 'fecha_reporte_nomina']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('firma_url');
        });
        Schema::dropIfExists('proyecto_usuario');
        Schema::dropIfExists('planta_usuario');
    }
};
