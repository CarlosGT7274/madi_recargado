<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->dropColumn('proyecto_id');

            $table->unsignedBigInteger('levantamiento_id')->after('id');
            $table->foreign('levantamiento_id')
                ->references('id')->on('levantamientos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['levantamiento_id']);
            $table->dropColumn('levantamiento_id');

            $table->unsignedBigInteger('proyecto_id')->after('id');
            $table->foreign('proyecto_id')
                ->references('id')->on('proyectos')
                ->onDelete('cascade');
        });
    }
};
