<?php

namespace App\Actions\Seguridad\Usuarios;

use App\Models\Empleado;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UsuariosAction
{
    public function list(): Collection
    {
        return User::with('roles:id,nombre')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'emailVerificado' => $u->email_verified_at !== null,
                'rolId' => $u->roles->first()?->id,
                'roles' => $u->roles->map(fn (Role $r) => ['id' => $r->id, 'nombre' => $r->nombre])->values(),
            ]);
    }

    /**
     * Empleados SIN cuenta de acceso (user_id NULL): la entidad de negocio
     * pura, asignable en Planeación/Nómina, que no necesariamente se
     * loguea. Vive aparte de list() porque no comparte forma con un User
     * (sin email, sin roles, sin password).
     */
    public function listEmpleadosSinCuenta(): Collection
    {
        return Empleado::whereNull('user_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'puesto', 'activo'])
            ->map(fn (Empleado $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'puesto' => $e->puesto,
                'activo' => $e->activo,
            ]);
    }

    /**
     * $data['tipo'] decide la rama:
     * - 'usuario': crea cuenta de acceso (users) + rol único (roles_usuarios).
     *   Puede autenticarse y le aplica el RBAC del sistema.
     * - 'empleado': crea solo el registro de negocio (empleados). Sin
     *   password, sin email, sin rol — no puede loguearse.
     */
    public function create(array $data): User|Empleado
    {
        if ($data['tipo'] === 'empleado') {
            return Empleado::create([
                'nombre' => $data['name'],
                'puesto' => $data['puesto'] ?? null,
                'activo' => true,
            ]);
        }

        $usuario = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (! empty($data['rol_id'])) {
            $usuario->roles()->sync([$data['rol_id']]);
        }

        return $usuario;
    }

    public function update(User $usuario, array $data): User
    {
        $rolId = $data['rol_id'] ?? null;
        unset($data['rol_id']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        // rol_id nullable a propósito: mandar null explícitamente quita
        // el rol (sync con array vacío), no significa "no tocar roles".
        $usuario->roles()->sync($rolId !== null ? [$rolId] : []);

        return $usuario;
    }

    public function delete(User $usuario): void
    {
        $usuario->delete();
    }

    public function deleteEmpleado(Empleado $empleado): void
    {
        abort_if($empleado->user_id !== null, 422, 'Este empleado tiene una cuenta de acceso vinculada; elimina primero el usuario.');

        $empleado->delete();
    }
}
