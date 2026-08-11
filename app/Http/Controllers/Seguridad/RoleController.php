<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\Roles\StoreRoleRequest;
use App\Http\Requests\Seguridad\Roles\UpdateRoleRequest;
use App\Models\Operacion;
use App\Models\Permiso;
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

    public function show(Role $role): Response
    {
        return Inertia::render('seguridad/roles/Show', [
            'role' => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'activo' => $role->activo,
                'usuarios_count' => $role->usuarios()->count(),
            ],
            'permisosArbol' => $this->arbolConOperaciones(null),
            'permisosAsignados' => $role->permisoOperaciones->pluck('id')->all(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function arbolConOperaciones(?int $padreId, ?string $prefijoPadre = null): array
    {
        return Permiso::where('activo', true)
            ->where('padre_id', $padreId)
            ->orderBy('nombre')
            ->get()
            ->map(function (Permiso $p) use ($prefijoPadre) {
                $endpoint = Permiso::componerEndpoint($prefijoPadre, $p->endpoint);
                $permisoOperacionesPorOperacionId = $p->permisoOperaciones()->pluck('id', 'operacion_id');

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'endpoint' => $endpoint,
                    'operaciones' => $p->operacionesAplicables()->get()
                        ->map(fn (Operacion $o) => [
                            'permisoOperacionId' => $permisoOperacionesPorOperacionId->get($o->id),
                            'clave' => $o->clave,
                            'nombre' => $o->nombre,
                        ])->all(),
                    'hijos' => $this->arbolConOperaciones($p->id, $endpoint),
                ];
            })->all();
    }

    public function permisos(Request $request, Role $role): RedirectResponse
    {
        $validado = $request->validate([
            'concesiones' => ['required', 'array'],
            'concesiones.*' => ['integer', 'exists:permiso_operaciones,id'],
        ]);

        $role->permisoOperaciones()->sync($validado['concesiones']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permisos actualizados.']);

        return back();
    }
}
