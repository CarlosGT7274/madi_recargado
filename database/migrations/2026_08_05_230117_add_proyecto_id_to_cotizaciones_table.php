<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['levantamiento_id']);
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('levantamiento_id')->nullable()->change();
            $table->foreign('levantamiento_id')->references('id')->on('levantamientos')->onDelete('cascade');

            $table->foreignId('proyecto_id')->nullable()->after('levantamiento_id')
                ->constrained('proyectos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->dropColumn('proyecto_id');
            $table->dropForeign(['levantamiento_id']);
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('levantamiento_id')->nullable(false)->change();
            $table->foreign('levantamiento_id')->references('id')->on('levantamientos')->onDelete('cascade');
        });
    }
};
