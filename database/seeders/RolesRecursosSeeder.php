<?php

namespace Database\Seeders;

use App\Models\Recurso;
use App\Models\Role;
use App\Support\Authorization\Permiso;
use Illuminate\Database\Seeder;

/**
 * Ejemplo de estructura de seed. Ajusta los slugs de recursos a tu catálogo real
 * (o mejor: dalos de alta desde un CRUD de "Recursos" en producción).
 */
class RolesRecursosSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['nombre' => 'Administrador', 'es_superadmin' => true, 'activo' => true]
        );

        $supervisor = Role::firstOrCreate(
            ['slug' => 'supervisor'],
            ['nombre' => 'Supervisor', 'es_superadmin' => false, 'activo' => true]
        );

        // Ejemplo de recursos; sustituye por los reales de tu sistema.
        $recursos = [
            ['slug' => 'usuarios', 'nombre' => 'Usuarios', 'modulo' => 'Sistema'],
            ['slug' => 'inventario', 'nombre' => 'Inventario', 'modulo' => 'Operación'],
        ];

        foreach ($recursos as $data) {
            $recurso = Recurso::firstOrCreate(['slug' => $data['slug']], $data);

            // admin es superadmin, no necesita filas en el pivot, pero si quieres explícito:
            $admin->recursos()->syncWithoutDetaching([$recurso->id => ['permisos' => Permiso::ALL]]);

            // supervisor: solo lectura y actualización, por ejemplo
            $supervisor->recursos()->syncWithoutDetaching([
                $recurso->id => ['permisos' => Permiso::READ | Permiso::UPDATE],
            ]);
        }
    }
}
