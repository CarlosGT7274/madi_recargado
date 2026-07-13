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
            $table->foreignId('compra_orden_id')->constrained('compras_ordenes')->cascadeOnDelete();
            $table->string('estado', 100);
            $table->text('observacion')->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_tracking');
    }
};
