<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Rol al que Fortify asigna a cualquier usuario recién registrado
        // (ver App\Actions\Fortify\CreateNewUser). Sin grants: no puede
        // nada hasta que un admin lo mueva a otro rol.
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

        // 15 = READ(1) + CREATE(2) + UPDATE(4) + DELETE(8) = todo.
        // Sin bypass: el grant explícito es lo que le da el acceso, punto.
        $superAdmin->otorgar($sistema, 15);
        $superAdmin->otorgar($inventario, 15);

        // supervisor: solo lectura en "sistema". "usuarios" no tiene grant
        // propio a propósito — hereda el READ de su padre subiendo por
        // padre_id. Así se ve la jerarquía funcionando de verdad.
        $supervisor->otorgar($sistema, 1);
        $supervisor->otorgar($inventario, 1 | 4); // lectura + actualizar

        unset($usuarios);
    }
}
