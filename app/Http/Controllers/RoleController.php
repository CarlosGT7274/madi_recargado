<?php

namespace App\Http\Controllers;

use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRolePermisosRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('usuarios')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
                'usuarios_count' => $role->usuarios_count,
            ]);

        return Inertia::render('roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return back();
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('permiso', ['Roles', Accion::DELETE]);

        if ($role->usuarios()->exists()) {
            return back()->withErrors([
                'role' => 'No puedes eliminar un rol que tiene usuarios asignados.',
            ]);
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado.']);

        return back();
    }

    public function permisos(UpdateRolePermisosRequest $request, Role $role): RedirectResponse
    {
        $sync = collect($request->validated('permisos'))
            ->filter(fn (int $bitmask): bool => $bitmask > 0)
            ->map(fn (int $bitmask): array => ['permisos' => $bitmask])
            ->all();

        $role->permisos()->sync($sync);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permisos actualizados.']);

        return back();
    }

    public function show(Role $role): Response
    {
        return Inertia::render('roles/Show', [
            'role' => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
                'usuarios_count' => $role->usuarios()->count(),
            ],
            'permisosArbol' => Permiso::arbol(),
            'permisosAsignados' => (object) $role->mapaPermisos(),
        ]);
    }
}
