<?php

// database/migrations/2026_08_11_000003_add_activo_to_operaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
