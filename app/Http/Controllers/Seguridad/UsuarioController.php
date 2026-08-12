<?php

namespace App\Http\Controllers\Seguridad;

use App\Actions\Seguridad\Usuarios\UsuariosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Seguridad\Usuarios\UpdateUsuarioRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(UsuariosAction $action): Response
    {
        return Inertia::render('seguridad/usuarios/Index', [
            'usuarios' => $action->list(),
            'rolesDisponibles' => Role::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(StoreUsuarioRequest $request, UsuariosAction $action): RedirectResponse
    {
        $action->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);

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
}
