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

        $sistema = Permiso::firstOrCreate(
            ['nombre' => 'Sistema'],
            ['padre_id' => null, 'endpoint' => null, 'activo' => true]
        );

        $usuarios = Permiso::firstOrCreate(
            ['nombre' => 'Usuarios'],
            ['padre_id' => $sistema->id, 'endpoint' => 'usuarios.index', 'activo' => true]
        );

        $inventario = Permiso::firstOrCreate(
            ['nombre' => 'Inventario'],
            ['padre_id' => null, 'endpoint' => 'inventario.index', 'activo' => true]
        );

        // Módulo nuevo: top-level a propósito, para que aparezca en el sidebar sin
        // necesitar todavía la jerarquía de menú (eso lo resolvemos cuando existan
        // más módulos bajo "Sistema").
        $roles = Permiso::firstOrCreate(
            ['nombre' => 'Roles'],
            ['padre_id' => null, 'endpoint' => 'roles.index', 'activo' => true]
        );

        $superAdmin->otorgar($sistema, Accion::READ);
        $superAdmin->otorgar($inventario, Accion::ALL);
        $superAdmin->otorgar($roles, Accion::ALL);

        $supervisor->otorgar($sistema, Accion::READ);
        $supervisor->otorgar($inventario, Accion::READ | Accion::UPDATE);

        unset($usuarios);
    }
}
