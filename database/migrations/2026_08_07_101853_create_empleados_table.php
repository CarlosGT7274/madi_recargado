<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `users` queda solo para autenticación. `empleados` es la entidad de
     * negocio real. `user_id` nullable+unique: no todo empleado inicia
     * sesión, pero todo user (que sea empleado) debe tener su registro aquí.
     */
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('puesto', 255)->nullable();
            $table->decimal('precio_hora_general', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
