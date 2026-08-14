<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revierte parcialmente 2026_08_03_225523_drop_para_correo_vendedor_moneda_from_cotizaciones_table:
     * `para` y `correo_vendedor` SÍ son parte del diseño real — el PDF
     * (pdf.cotizacion.blade.php) siempre los mostró en INFORMACIÓN
     * GENERAL, solo que desde el drop quedaban en null ("—"). `moneda`
     * NO se regresa: esa sigue fija en código (Cotizacion::MONEDA_FIJA).
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('para', 255)->nullable()->after('folio');
            $table->string('correo_vendedor', 255)->nullable()->after('vendedor');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['para', 'correo_vendedor']);
        });
    }
};
