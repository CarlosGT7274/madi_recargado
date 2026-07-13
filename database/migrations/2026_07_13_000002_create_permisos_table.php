<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->foreign('padre_id')->references('id')->on('permisos')->onDelete('set null');
            $table->string('nombre', 100);
            $table->string('endpoint', 100)->nullable();
            $table->boolean('activo')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
