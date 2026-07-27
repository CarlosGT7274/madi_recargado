<?php

namespace App\Actions\Seguridad\Roles;

use App\Exceptions\Seguridad\RolConUsuariosAsignadosException;
use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * Agrupa todas las operaciones CRUD y de negocio del módulo Roles.
 * Mantiene la lógica en un único archivo coherente, cumpliendo con la regla
 * de "no proliferar archivos" para operaciones triviales.
 */
class RolesAction
{
    /** Listar todos los roles con su número de usuarios */
    public function list(): Collection
    {
        return Role::withCount('usuarios')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
                'usuarios_count' => $role->usuarios_count,
            ]);
    }

    /** Obtener detalle de un rol */
    public function detail(Role $role): array
    {
        return [
            'role' => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
            ],
            'permisosArbol' => $role->permisosArbol(),
            'permisosAsignados' => (object) $role->mapaPermisos(),
        ];
    }

    /** Crear un nuevo rol */
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    /** Actualizar un rol existente */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role;
    }

    /** Sincronizar permisos de un rol */
    public function syncPermissions(Role $role, array $permisos): void
    {
        // Convert [permisoId => bitmask] into the format expected by sync():
        // [permisoId => ['permisos' => bitmask]]
        $pivotData = [];
        foreach ($permisos as $id => $mask) {
            $pivotData[$id] = ['permisos' => $mask];
        }
        $role->permisos()->sync($pivotData);
    }

    /** Eliminar un rol, asegurando que no tenga usuarios asignados */
    public function delete(Role $role): void
    {
        if ($role->usuarios()->exists()) {
            throw new RolConUsuariosAsignadosException($role);
        }

        $role->delete();
    }
}
