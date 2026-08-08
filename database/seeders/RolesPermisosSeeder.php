<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Seeder;

class RolesPermisosSeeder extends Seeder
{
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

        // Super Administrador: ALL en todo, sin excepción. Es la raíz de
        // confianza del sistema — cualquier permiso parcial aquí es un bug,
        // no una decisión de negocio (ver caso ingenierias => READ, que
        // dejaba sin botones de aprobación a este mismo rol).
        foreach ([$sistema, $inventario, $seguridad, $roles, $usuarios, $ingenierias, $plantas] as $permiso) {
            $superAdmin->otorgar($permiso, Accion::ALL);
        }

        $supervisor->otorgar($sistema, Accion::READ);
        $supervisor->otorgar($inventario, Accion::READ | Accion::UPDATE);
    }
}
