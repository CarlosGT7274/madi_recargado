<?php

// database/migrations/2026_08_13_000001_seed_enviar_rechazar_operaciones.php

use App\Models\Operacion;
use App\Models\Permiso;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Las rutas `ingenierias.planeacion.enviar` y `.rechazar` ya usaban
     * `middleware('permiso:enviar')` / `permiso:rechazar`, pero el
     * catálogo de operaciones (seed_operaciones_catalogo) nunca las
     * incluyó — solo llegó hasta ver/crear/editar/eliminar/aprobar/
     * supervisar. Sin la fila en `operaciones`, Role::tieneOperacion()
     * jamás encuentra la clave y VerificarPermiso deniega con 403 sin
     * importar el rol. bit 64/128 son los últimos disponibles: el
     * bitmask de roles_permisos.permisos es unsignedTinyInteger (8 bits,
     * máximo 255) — no queda espacio para operaciones adicionales sin
     * migrar esa columna a un tipo más grande.
     */
    public function up(): void
    {
        $catalogo = [
            ['clave' => 'enviar', 'nombre' => 'Enviar', 'bit' => 64, 'basica' => false, 'orden' => 7],
            ['clave' => 'rechazar', 'nombre' => 'Rechazar', 'bit' => 128, 'basica' => false, 'orden' => 8],
        ];

        foreach ($catalogo as $operacion) {
            Operacion::firstOrCreate(['clave' => $operacion['clave']], $operacion);
        }

        $enviar = Operacion::where('clave', 'enviar')->first();
        $rechazar = Operacion::where('clave', 'rechazar')->first();

        $planeacion = Permiso::whereHas('padre', fn ($q) => $q->where('endpoint', 'ingenierias'))
            ->where('endpoint', 'planeacion')
            ->first();

        if ($planeacion !== null) {
            $planeacion->operaciones()->syncWithoutDetaching(array_filter([$enviar?->id, $rechazar?->id]));
        }

        // Declarar la operación como "aplicable" no la concede a ningún rol
        // (eso vive en roles_permisos.permisos, dato por rol). Hay que
        // entrar a Seguridad → Roles → [rol] y marcar las casillas
        // Enviar/Rechazar para los roles que deban tenerlas.
    }

    public function down(): void
    {
        // Catálogo aditivo: no se revierte para no romper roles ya configurados con estos bits.
    }
};
