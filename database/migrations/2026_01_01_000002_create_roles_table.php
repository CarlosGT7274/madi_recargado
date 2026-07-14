<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique(); // ej: "admin", "supervisor" (identificador estable, no el bitmask)
            $table->string('nombre', 150);
            $table->boolean('es_superadmin')->default(false); // bypass total, en vez de "permisos" = 15 en todo
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
