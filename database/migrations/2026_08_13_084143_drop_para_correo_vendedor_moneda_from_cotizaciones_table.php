<?php

// database/migrations/2026_08_13_000000_drop_para_correo_vendedor_moneda_from_cotizaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `para` y `correo_vendedor` nunca fueron parte del diseño real de
     * Cotización — se colaron como campos inventados. `moneda` se decidió
     * fija en código (ver Cotizacion::MONEDA_FIJA), igual que la
     * dirección de MADI, así que tampoco tiene sentido persistirla por
     * cotización.
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['para', 'correo_vendedor', 'moneda']);
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('para', 255)->nullable();
            $table->string('correo_vendedor', 255)->nullable();
            $table->string('moneda', 50)->default('PESOS MXN');
        });
    }
};
