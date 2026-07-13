<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_orden_id');
            $table->foreign('compra_orden_id')->references('id')->on('compras_ordenes')->onDelete('cascade');
            $table->string('estado', 100);
            $table->text('observacion')->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->string('usuario', 255);
            $table->timestamp('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_tracking');
    }
};
