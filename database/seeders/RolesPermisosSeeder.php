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
            ['padre_id' => null, 'endpoint' => null, 'activo' => true]
        );

        $roles = Permiso::updateOrCreate(
            ['nombre' => 'Roles'],
            ['padre_id' => $seguridad->id, 'endpoint' => 'roles', 'activo' => true]
        );

        $usuarios = Permiso::updateOrCreate(
            ['nombre' => 'Usuarios'],
            ['padre_id' => $seguridad->id, 'endpoint' => 'usuarios', 'activo' => true]
        );

        $superAdmin->otorgar($seguridad, Accion::ALL);
        $superAdmin->otorgar($sistema, Accion::READ);
        $superAdmin->otorgar($inventario, Accion::ALL);
        $superAdmin->otorgar($roles, Accion::ALL);
        $superAdmin->otorgar($usuarios, Accion::ALL);

        $supervisor->otorgar($sistema, Accion::READ);
        $supervisor->otorgar($inventario, Accion::READ | Accion::UPDATE);
    }
}
