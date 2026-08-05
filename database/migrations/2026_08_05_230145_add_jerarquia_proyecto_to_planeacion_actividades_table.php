<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Habilita planeacion_actividades para actuar también como árbol de
     * "partidas" (actividades) de un Proyecto directo, sin depender de una
     * Planeación semanal: parent_id es la autorreferencia padre/hija,
     * proyecto_id ancla el árbol al proyecto. planeacion_id y dia_semana
     * quedan opcionales porque solo aplican al flujo de planeación semanal.
     */
    public function up(): void
    {
        Schema::table('planeacion_actividades', function (Blueprint $table) {
            $table->dropForeign(['planeacion_id']);
        });

        Schema::table('planeacion_actividades', function (Blueprint $table) {
            $table->unsignedBigInteger('planeacion_id')->nullable()->change();
            $table->foreign('planeacion_id')->references('id')->on('planeaciones')->onDelete('cascade');

            $table->enum('dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])
                ->nullable()->change();

            $table->unsignedBigInteger('parent_id')->nullable()->after('partida_id');
            $table->foreign('parent_id')->references('id')->on('planeacion_actividades')->onDelete('cascade');

            $table->unsignedBigInteger('proyecto_id')->nullable()->after('parent_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('planeacion_actividades', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['proyecto_id', 'parent_id']);

            $table->dropForeign(['planeacion_id']);
        });

        Schema::table('planeacion_actividades', function (Blueprint $table) {
            $table->unsignedBigInteger('planeacion_id')->nullable(false)->change();
            $table->foreign('planeacion_id')->references('id')->on('planeaciones')->onDelete('cascade');

            $table->enum('dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])
                ->nullable(false)->change();
        });
    }
};
