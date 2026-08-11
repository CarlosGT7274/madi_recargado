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

    public function detail(Role $role): array
    {
        $role->loadCount('usuarios');
        $role->load('permisoOperaciones.operacion');

        $asignados = [];
        foreach ($role->permisoOperaciones as $po) {
            $asignados[$po->permiso_id][] = $po->operacion->clave;
        }

        return [
            'role' => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
                'usuarios_count' => $role->usuarios_count,
            ],
            'permisosArbol' => Permiso::arbol(),
            'permisosAsignados' => (object) $asignados,
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
        $role->permisoOperaciones()->detach();

        foreach ($permisos as $permisoId => $operaciones) {
            $permiso = Permiso::find($permisoId);
            if (! $permiso) {
                continue;
            }

            foreach ((array) $operaciones as $clave) {
                try {
                    $role->otorgarOperacion($permiso, $clave);
                } catch (\InvalidArgumentException $e) {
                    // Ignorar
                }
            }
        }
    }

    public function delete(Role $role): void
    {
        if ($role->usuarios()->exists()) {
            throw new RolConUsuariosAsignadosException($role);
        }

        $role->delete();
    }
}
