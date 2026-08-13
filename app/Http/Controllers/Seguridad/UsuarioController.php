<?php

namespace App\Http\Controllers\Seguridad;

use App\Actions\Seguridad\Roles\RolesAction;
use App\Actions\Seguridad\Usuarios\UsuariosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Seguridad\Usuarios\UpdateUsuarioRequest;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(UsuariosAction $action, RolesAction $rolesAction): Response
    {
        return Inertia::render('seguridad/usuarios/Index', [
            'usuarios' => $action->list(),
            'empleadosSinCuenta' => $action->listEmpleadosSinCuenta(),
            'rolesDisponibles' => $rolesAction->list()->where('activo', true)->values(),
        ]);
    }

    /** Único endpoint de alta: $request->tipo ('usuario' | 'empleado') decide qué crea la Action. */
    public function store(StoreUsuarioRequest $request, UsuariosAction $action): RedirectResponse
    {
        $esUsuario = $request->validated('tipo') === 'usuario';

        $action->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $esUsuario ? 'Usuario creado.' : 'Empleado creado.',
        ]);

        return back();
    }

    public function update(UpdateUsuarioRequest $request, User $usuario, UsuariosAction $action): RedirectResponse
    {
        $action->update($usuario, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return back();
    }

    public function destroy(User $usuario, UsuariosAction $action): RedirectResponse
    {
        $action->delete($usuario);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return back();
    }

    public function destroyEmpleado(Empleado $empleado, UsuariosAction $action): RedirectResponse
    {
        $action->deleteEmpleado($empleado);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empleado eliminado.']);

        return back();
    }
}
