<?php

namespace App\Actions\Seguridad\Roles;

use App\Exceptions\Seguridad\RolConUsuariosAsignadosException;
use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Support\Collection;

class RolesAction
{
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
        $role->loadCount('usuarios');

        return [
            'role' => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
                'usuarios_count' => $role->usuarios_count,
            ],
            'permisosArbol' => Permiso::arbol(),
            'permisosAsignados' => (object) $role->mapaPermisos(),
        ];
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role;
    }

    public function syncPermissions(Role $role, array $permisos): void
    {
        $pivotData = [];
        foreach ($permisos as $id => $mask) {
            $pivotData[$id] = ['permisos' => $mask];
        }
        $role->permisos()->sync($pivotData);
    }

    public function delete(Role $role): void
    {
        if ($role->usuarios()->exists()) {
            throw new RolConUsuariosAsignadosException($role);
        }

        $role->delete();
    }
}
