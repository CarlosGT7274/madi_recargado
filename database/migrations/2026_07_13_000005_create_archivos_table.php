<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->string('archivable_type', 50);
            $table->unsignedInteger('archivable_id');
            $table->enum('almacenamiento', ['base64', 'url'])->default('base64');
            $table->string('nombre_archivo', 255)->nullable();
            $table->enum('tipo_archivo', ['imagen', 'pdf', 'excel', 'word', 'otro'])->default('otro');
            $table->string('tipo_mime', 100)->nullable();
            $table->unsignedInteger('tamano_bytes')->nullable();
            $table->longText('contenido_base64')->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('storage_driver', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
