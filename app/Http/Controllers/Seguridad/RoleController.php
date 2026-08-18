<?php

namespace App\Http\Controllers\Seguridad;

use App\Actions\Seguridad\Roles\RolesAction;
use App\Exceptions\Seguridad\RolConUsuariosAsignadosException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\Roles\StoreRoleRequest;
use App\Http\Requests\Seguridad\Roles\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('seguridad/roles/Index', [
            'roles' => Role::withCount('usuarios')->orderBy('nombre')->get()
                ->map(fn (Role $r) => [
                    'id' => $r->id,
                    'nombre' => $r->nombre,
                    'activo' => $r->activo,
                    'usuarios_count' => $r->usuarios_count,
                ]),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create([...$request->validated(), 'activo' => $request->validated('activo') ?? true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return back();
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return back();
    }

    public function destroy(Role $role, RolesAction $roleAction): RedirectResponse
    {
        try {
            $roleAction->delete($role);
        } catch (RolConUsuariosAsignadosException $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado.']);

        return redirect()->route('seguridad.roles.index');
    }

    public function show(Role $role, RolesAction $roleAction): Response
    {
        $detalle = $roleAction->detail($role);

        return Inertia::render('seguridad/roles/Show', [
            'role' => $detalle['role'],
            'permisosArbol' => $detalle['permisosArbol'],
            'permisosAsignados' => (object) $detalle['permisosAsignados'],
            'operaciones' => $detalle['operaciones'],
        ]);
    }

    public function permisos(Request $request, Role $role, RolesAction $action): RedirectResponse
    {
        $validado = $request->validate([
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer'],
        ]);

        $action->syncPermissions($role, $validado['permisos'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permisos actualizados.']);

        return back();
    }
}
