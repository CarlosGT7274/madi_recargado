<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Recursos" son las entidades autorizables del sistema (usuarios,
     * inventario, compras, etc.), en árbol vía padre_id — igual que tu
     * `permisos` original, pero con un slug estable para referenciarlos
     * en código/rutas en vez de un endpoint de ruta.
     *
     * Viven en base de datos, no como enum en PHP.
     */
    public function up(): void
    {
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')->nullable()->constrained('recursos')->nullOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
