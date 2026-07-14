<?php

namespace Database\Seeders;

use App\Models\Recurso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Seeder;

/**
 * Ejemplo de estructura de seed con jerarquía real (padre_id). Ajusta los
 * slugs a tu catálogo real, o mejor: dalos de alta desde un CRUD de
 * "Recursos" en producción.
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

        // Nodo padre: "sistema" agrupa usuarios y roles.
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

        // admin es superadmin: el bypass ya lo cubre, pero puedes ser
        // explícito también (útil si algún día quitas el bypass).
        $admin->otorgar($sistema, Accion::ALL);
        $admin->otorgar($inventario, Accion::ALL);

        // supervisor: acceso de solo lectura al módulo "sistema" completo
        // (usuarios NO tiene grant propio, hereda READ de su padre "sistema").
        $supervisor->otorgar($sistema, Accion::READ);

        // inventario: supervisor sí puede leer y actualizar, explícito.
        $supervisor->otorgar($inventario, Accion::READ | Accion::UPDATE);

        // Nota: $usuarios nunca recibe un grant propio para "supervisor" a
        // propósito — así se ve la herencia funcionando: tienePermiso()
        // sube de "usuarios" a "sistema" y encuentra el READ ahí.
        unset($usuarios);
    }
}
