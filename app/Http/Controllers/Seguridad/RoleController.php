<?php

namespace App\Http\Controllers\Seguridad;

use App\Actions\Seguridad\Roles\RolesAction;
use App\Exceptions\Seguridad\RolConUsuariosAsignadosException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\Roles\StoreRoleRequest;
use App\Http\Requests\Seguridad\Roles\UpdateRolePermisosRequest;
use App\Http\Requests\Seguridad\Roles\UpdateRoleRequest;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(RolesAction $roleAction): Response
    {
        return Inertia::render('seguridad/roles/Index', [
            'roles' => $roleAction->list(),
        ]);
    }

    public function store(StoreRoleRequest $request, RolesAction $roleAction): RedirectResponse
    {
        $roleAction->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return back();
    }

    public function update(UpdateRoleRequest $request, Role $role, RolesAction $roleAction): RedirectResponse
    {
        $roleAction->update($role, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return back();
    }

    public function destroy(Role $role, RolesAction $roleAction): RedirectResponse
    {
        Gate::authorize('permiso', ['Roles', Accion::DELETE]);

        try {
            $roleAction->delete($role);
        } catch (RolConUsuariosAsignadosException $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado.']);

        return back();
    }

    public function permisos(
        UpdateRolePermisosRequest $request,
        Role $role,
        RolesAction $roleAction
    ): RedirectResponse {
        $roleAction->syncPermissions($role, $request->validated('permisos'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permisos actualizados.']);

        return back();
    }

    public function show(Role $role, RolesAction $roleAction): Response
    {
        $detalle = $roleAction->detail($role);

        return Inertia::render('seguridad/roles/Show', [
            'role' => $detalle['role'],
            'permisosArbol' => $detalle['permisosArbol'],
            'permisosAsignados' => (object) $detalle['permisosAsignados'],
        ]);
    }
}
