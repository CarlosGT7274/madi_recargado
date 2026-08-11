<?php

namespace Database\Seeders;

use App\Models\Operacion;
use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Seeder;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Catálogo base de operaciones. Las 4 primeras son CRUD; el resto
     * son ejemplos de operaciones de negocio no-CRUD, ya usadas en el
     * flujo real de Planeación (enviar/aprobar/rechazar), para que el
     * catálogo no se quede vacío de ejemplos no-CRUD en esta etapa.
     *
     * @var array<string, string> clave => nombre
     */
    private const OPERACIONES_BASE = [
        'ver' => 'Ver',
        'crear' => 'Crear',
        'actualizar' => 'Actualizar',
        'eliminar' => 'Eliminar',
        'enviar' => 'Enviar',
        'aprobar' => 'Aprobar',
        'rechazar' => 'Rechazar',
        'archivar' => 'Archivar',
        'firmar' => 'Firmar',
    ];

    public function run(): void
    {
        foreach (self::OPERACIONES_BASE as $clave => $nombre) {
            Operacion::firstOrCreate(['clave' => $clave], ['nombre' => $nombre, 'activo' => true]);
        }

        Role::firstOrCreate(['nombre' => 'Sin Asignar'], ['activo' => true]);
        $superAdmin = Role::firstOrCreate(['nombre' => 'Super Administrador'], ['activo' => true]);
        $supervisor = Role::firstOrCreate(['nombre' => 'Supervisor'], ['activo' => true]);

        $sistema = Permiso::updateOrCreate(
            ['nombre' => 'Sistema'],
            ['padre_id' => null, 'endpoint' => null, 'activo' => true]
        );
        $inventario = Permiso::updateOrCreate(
            ['nombre' => 'Inventario'],
            ['padre_id' => null, 'endpoint' => 'inventario', 'activo' => true]
        );
        $seguridad = Permiso::updateOrCreate(
            ['nombre' => 'Seguridad'],
            ['padre_id' => null, 'endpoint' => 'seguridad', 'activo' => true]
        );
        $roles = Permiso::updateOrCreate(
            ['nombre' => 'Roles'],
            ['padre_id' => $seguridad->id, 'endpoint' => 'roles', 'activo' => true]
        );
        $usuarios = Permiso::updateOrCreate(
            ['nombre' => 'Usuarios'],
            ['padre_id' => $seguridad->id, 'endpoint' => 'usuarios', 'activo' => true]
        );
        $ingenierias = Permiso::updateOrCreate(
            ['nombre' => 'Ingenierías'],
            ['padre_id' => null, 'endpoint' => 'ingenierias', 'activo' => true]
        );
        $plantas = Permiso::updateOrCreate(
            ['nombre' => 'Plantas'],
            ['padre_id' => $ingenierias->id, 'endpoint' => 'plantas', 'activo' => true]
        );
        $planeacion = Permiso::updateOrCreate(
            ['nombre' => 'Planeación'],
            ['padre_id' => null, 'endpoint' => 'planeacion', 'activo' => true]
        );

        $objetosCrud = [$sistema, $inventario, $seguridad, $roles, $usuarios, $ingenierias, $plantas];

        foreach ($objetosCrud as $objeto) {
            $objeto->declararOperaciones(Accion::crud());
        }

        // Planeación declara CRUD + las operaciones de negocio propias
        // de su flujo, ya implementadas en PlaneacionesAction.
        $planeacion->declararOperaciones([...Accion::crud(), 'enviar', 'aprobar', 'rechazar']);

        // Super Administrador: todas las operaciones declaradas sobre
        // todo el catálogo — raíz de confianza, sin excepción.
        foreach ([...$objetosCrud, $planeacion] as $objeto) {
            foreach ($objeto->operacionesAplicables()->pluck('clave') as $clave) {
                $superAdmin->otorgarOperacion($objeto, $clave);
            }
        }

        $supervisor->otorgarOperacion($sistema, Accion::READ);
        $supervisor->otorgarOperacion($inventario, Accion::READ);
        $supervisor->otorgarOperacion($inventario, Accion::UPDATE);
        $supervisor->otorgarOperacion($planeacion, Accion::READ);
        $supervisor->otorgarOperacion($planeacion, 'aprobar');
        $supervisor->otorgarOperacion($planeacion, 'rechazar');
    }
}
