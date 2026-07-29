<?php

use App\Support\Accion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El permiso "Levantamientos" es referenciado por nombre en
     * StoreLevantamientoRequest, UpdateLevantamientoRequest y en el
     * Gate del LevantamientoController::destroy, y por endpoint
     * (`ingenierias.levantamientos`) tanto en el middleware de rutas como
     * en el frontend. Sin embargo, solo existía en el seeder, por lo que
     * las bases de datos ya migradas nunca lo tuvieron y `puede('Levantamientos', ...)`
     * siempre devolvía false → 403 "This action is unauthorized.".
     *
     * Esta migración lo inserta de forma idempotente como hijo de Ingenierías
     * (segmento `levantamientos` => endpoint completo `ingenierias.levantamientos`)
     * y lo otorga a cualquier rol "Super Administrador" existente, para arreglar
     * las instalaciones que ya corrieron las migraciones sin re-seedear.
     */
    public function up(): void
    {
        $ingenieriasId = DB::table('permisos')
            ->whereNull('padre_id')
            ->where('endpoint', 'ingenierias')
            ->value('id');

        // Si aún no existe el módulo padre, lo creamos para poder anidar.
        if ($ingenieriasId === null) {
            $ingenieriasId = DB::table('permisos')->insertGetId([
                'padre_id' => null,
                'nombre' => 'Ingenierías',
                'endpoint' => 'ingenierias',
                'activo' => true,
            ]);
        }

        $levantamientosId = DB::table('permisos')
            ->where('padre_id', $ingenieriasId)
            ->where('endpoint', 'levantamientos')
            ->value('id');

        if ($levantamientosId === null) {
            $levantamientosId = DB::table('permisos')->insertGetId([
                'padre_id' => $ingenieriasId,
                'nombre' => 'Levantamientos',
                'endpoint' => 'levantamientos',
                'activo' => true,
            ]);
        }

        $superAdminIds = DB::table('roles')
            ->where('nombre', 'Super Administrador')
            ->pluck('id');

        foreach ($superAdminIds as $rolId) {
            DB::table('roles_permisos')->updateOrInsert(
                ['rol_id' => $rolId, 'permiso_id' => $levantamientosId],
                ['permisos' => Accion::ALL],
            );
        }
    }

    public function down(): void
    {
        $ingenieriasId = DB::table('permisos')
            ->whereNull('padre_id')
            ->where('endpoint', 'ingenierias')
            ->value('id');

        if ($ingenieriasId === null) {
            return;
        }

        $levantamientosId = DB::table('permisos')
            ->where('padre_id', $ingenieriasId)
            ->where('endpoint', 'levantamientos')
            ->value('id');

        if ($levantamientosId === null) {
            return;
        }

        DB::table('roles_permisos')->where('permiso_id', $levantamientosId)->delete();
        DB::table('permisos')->where('id', $levantamientosId)->delete();
    }
};
