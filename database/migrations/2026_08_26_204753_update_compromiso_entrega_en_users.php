<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quita las columnas viejas de corte recurrente, solo si siguen ahí.
        Schema::table('users', function (Blueprint $table) {
            foreach (['corte_dia_semana', 'corte_hora', 'corte_semana_relativa'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Agrega compromiso_fecha, solo si no existe ya.
        if (! Schema::hasColumn('users', 'compromiso_fecha')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dateTime('compromiso_fecha')->nullable()->after('firma_url');
            });
        }

        // corte_nota nunca existió en `users`, así que no hay nada que
        // renombrar: se crea compromiso_nota directo. Si en algún ambiente
        // sí llegara a existir corte_nota, se renombra en vez de duplicar.
        if (Schema::hasColumn('users', 'corte_nota') && ! Schema::hasColumn('users', 'compromiso_nota')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('corte_nota', 'compromiso_nota');
            });
        } elseif (! Schema::hasColumn('users', 'compromiso_nota')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('compromiso_nota')->nullable()->after('compromiso_fecha');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'compromiso_fecha')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('compromiso_fecha');
            });
        }

        if (Schema::hasColumn('users', 'compromiso_nota')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('compromiso_nota');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'corte_dia_semana')) {
                $table->enum('corte_dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])
                    ->nullable()->after('firma_url');
            }
            if (! Schema::hasColumn('users', 'corte_hora')) {
                $table->time('corte_hora')->nullable()->after('corte_dia_semana');
            }
            if (! Schema::hasColumn('users', 'corte_semana_relativa')) {
                $table->enum('corte_semana_relativa', ['actual', 'anterior'])->default('anterior')->after('corte_hora');
            }
        });
    }
};
