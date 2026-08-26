<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compromisos_entrega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Ambos nullable: null en los dos = compromiso general del
            // usuario. Solo planta_id = aplica a toda esa planta. Ambos =
            // aplica solo a ese proyecto puntual. compromisoVigentePara()
            // en el modelo User es quien resuelve cuál gana para una
            // Planeación concreta.
            $table->foreignId('planta_id')->nullable()->constrained('plantas')->nullOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->dateTime('fecha');
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'fecha']);
        });
        // Las columnas viejas (compromiso único por usuario) quedan
        // reemplazadas por la tabla de arriba. hasColumn() por si en tu
        // ambiente ya se habían quitado en algún intento anterior.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'compromiso_fecha')) {
                $table->dropColumn('compromiso_fecha');
            }
            if (Schema::hasColumn('users', 'compromiso_nota')) {
                $table->dropColumn('compromiso_nota');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'compromiso_fecha')) {
                $table->dateTime('compromiso_fecha')->nullable()->after('firma_url');
            }
            if (! Schema::hasColumn('users', 'compromiso_nota')) {
                $table->text('compromiso_nota')->nullable()->after('compromiso_fecha');
            }
        });
        Schema::dropIfExists('compromisos_entrega');
    }
};
