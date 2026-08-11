<?php

use App\Models\Operacion;
use App\Models\Permiso;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Las 4 básicas (mismos bits que ya usaba el bitmask) + las primeras
     * operaciones de negocio. Nuevas operaciones/aplicabilidad se agregan
     * en migraciones futuras — nunca hardcodeadas en el frontend.
     */
    public function up(): void
    {
        $catalogo = [
            ['clave' => 'ver', 'nombre' => 'Ver', 'bit' => 1, 'basica' => true, 'orden' => 1],
            ['clave' => 'crear', 'nombre' => 'Crear', 'bit' => 2, 'basica' => true, 'orden' => 2],
            ['clave' => 'editar', 'nombre' => 'Editar', 'bit' => 4, 'basica' => true, 'orden' => 3],
            ['clave' => 'eliminar', 'nombre' => 'Eliminar', 'bit' => 8, 'basica' => true, 'orden' => 4],
            ['clave' => 'aprobar', 'nombre' => 'Aprobar', 'bit' => 16, 'basica' => false, 'orden' => 5],
            ['clave' => 'supervisar', 'nombre' => 'Supervisar', 'bit' => 32, 'basica' => false, 'orden' => 6],
        ];

        foreach ($catalogo as $operacion) {
            Operacion::firstOrCreate(['clave' => $operacion['clave']], $operacion);
        }

        $aprobar = Operacion::where('clave', 'aprobar')->first();
        $supervisar = Operacion::where('clave', 'supervisar')->first();

        $planeacion = Permiso::whereHas('padre', fn ($q) => $q->where('endpoint', 'ingenierias'))
            ->where('endpoint', 'planeacion')
            ->first();

        if ($planeacion !== null && $aprobar !== null && $supervisar !== null) {
            $planeacion->operaciones()->syncWithoutDetaching([$aprobar->id, $supervisar->id]);
        }
    }

    public function down(): void
    {
        // Catálogo aditivo: no se revierte para no romper roles ya configurados con estos bits.
    }
};
