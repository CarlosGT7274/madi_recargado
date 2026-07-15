<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El único bitmask del sistema: READ=1, CREATE=2, UPDATE=4, DELETE=8,
     * ALL=15. Por (rol, permiso). Referenciado por id, sin slugs.
     */
    public function up(): void
    {
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->unsignedTinyInteger('permisos')->default(0);

            $table->unique(['rol_id', 'permiso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};
