
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Árbol de recursos autorizables, fiel a tu diseño original (madi.sql):
     * `padre` (ahora `padre_id`, por convención de Eloquent) y `endpoint`
     * para que el middleware detecte la ruta actual sin que la escribas
     * a mano en cada definición de ruta.
     *
     * Sin `slug`: se referencia por `nombre` o por `id`, como preferiste.
     */
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')->nullable()->constrained('permisos')->nullOnDelete();
            $table->string('nombre', 150);
            // Solo el segmento propio (p. ej. `seguridad`, `roles`). El endpoint
            // completo (`seguridad.roles`) se deriva de la jerarquía en código.
            // Un segmento es único dentro de su padre, no en todo el sistema.
            $table->string('endpoint', 150)->nullable();
            $table->boolean('activo')->default(true);

            $table->unique(['padre_id', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
