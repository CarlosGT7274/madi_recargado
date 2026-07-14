<?php

namespace Database\Seeders;

use App\Models\Recurso;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesRecursosSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['nombre' => 'Super Administrador', 'es_superadmin' => true, 'activo' => true]
        );

        $supervisor = Role::firstOrCreate(
            ['slug' => 'supervisor'],
            ['nombre' => 'Supervisor', 'es_superadmin' => false, 'activo' => true]
        );

        $sistema = Recurso::firstOrCreate(
            ['slug' => 'sistema'],
            ['padre_id' => null, 'nombre' => 'Sistema', 'activo' => true]
        );

        $usuarios = Recurso::firstOrCreate(
            ['slug' => 'usuarios'],
            ['padre_id' => $sistema->id, 'nombre' => 'Usuarios', 'activo' => true]
        );

        $inventario = Recurso::firstOrCreate(
            ['slug' => 'inventario'],
            ['padre_id' => null, 'nombre' => 'Inventario', 'activo' => true]
        );

        // 15 = READ(1) + CREATE(2) + UPDATE(4) + DELETE(8) = todo.
        // super-admin ya tiene bypass total por es_superadmin=true, pero se
        // deja el grant explícito por si algún día se retira el bypass.
        $superAdmin->otorgar($sistema, 15);
        $superAdmin->otorgar($inventario, 15);

        // supervisor: solo lectura en "sistema" (1 = READ).
        // "usuarios" no recibe grant propio a propósito: hereda el READ de
        // su padre "sistema" subiendo por padre_id — así se ve la jerarquía
        // funcionando de verdad, no solo declarada.
        $supervisor->otorgar($sistema, 1);

        // supervisor en inventario: lectura + actualizar (1 + 4 = 5).
        $supervisor->otorgar($inventario, 5);

        unset($usuarios);
    }
}
