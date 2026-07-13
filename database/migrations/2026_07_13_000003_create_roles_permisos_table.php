<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->unsignedBigInteger('rol_id');
            $table->foreign('rol_id')->references('id')->on('roles')->onDelete('cascade');
            $table->unsignedBigInteger('permiso_id');
            $table->foreign('permiso_id')->references('id')->on('permisos')->onDelete('cascade');
            // TODO revisar manualmente: valor TINYINT(4) NOT NULL DEFAULT 0
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};
