<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Recursos" son las entidades autorizables del sistema (usuarios, inventario, compras, etc.)
     * Viven en base de datos, NO como enum en PHP. Se dan de alta desde un seeder/CRUD,
     * no se hardcodean nombres de permisos por recurso.
     */
    public function up(): void
    {
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique(); // ej: "usuarios", "inventario", "compras"
            $table->string('nombre', 150);          // nombre visible, editable en runtime
            $table->string('modulo', 100)->nullable(); // agrupador opcional (para UI de administración de roles)
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
