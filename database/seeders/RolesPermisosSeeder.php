<?php

namespace Database\Seeders;

use App\Models\Operacion;
use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Bits del bitmask en roles_permisos.permisos — deben coincidir con
     * operaciones.bit en el catálogo. NO uses App\Support\Accion aquí:
     * Accion ahora son claves de texto ('ver', 'crear', ...) para el
     * middleware `permiso:`, un concepto distinto al bitmask numérico.
     */
    private const VER = 1;

    private const CREAR = 2;

    private const EDITAR = 4;

    private const ELIMINAR = 8;

    private const APROBAR = 16;

    private const SUPERVISAR = 32;

    private const CRUD = self::VER | self::CREAR | self::EDITAR | self::ELIMINAR;

    private const TODO = self::CRUD | self::APROBAR | self::SUPERVISAR;

    public function run(): void
    {
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
            ['padre_id' => $ingenierias->id, 'endpoint' => 'planeacion', 'activo' => true]
        );

        // Aprobar/Supervisar solo aplican como objeto en Planeación — el
        // resto de módulos se queda en N/A para esas dos operaciones.
        $aprobar = Operacion::where('clave', 'aprobar')->first();
        $supervisar = Operacion::where('clave', 'supervisar')->first();

        if ($aprobar && $supervisar) {
            $planeacion->operaciones()->syncWithoutDetaching([$aprobar->id, $supervisar->id]);
        }

        // Super Administrador: TODO en todo, sin excepción. Es la raíz de
        // confianza del sistema — cualquier permiso parcial aquí es un bug,
        // no una decisión de negocio.
        foreach ([$sistema, $inventario, $seguridad, $roles, $usuarios, $ingenierias, $plantas, $planeacion] as $permiso) {
            $superAdmin->otorgar($permiso, self::TODO);
        }

        $supervisor->otorgar($sistema, self::VER);
        $supervisor->otorgar($inventario, self::VER | self::EDITAR);
        $supervisor->otorgar($planeacion, self::VER | self::APROBAR | self::SUPERVISAR);
    }
}
