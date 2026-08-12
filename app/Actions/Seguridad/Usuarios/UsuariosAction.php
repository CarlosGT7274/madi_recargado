<?php

namespace App\Actions\Seguridad\Usuarios;

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
                'roles' => $u->roles->map(fn (Role $r) => ['id' => $r->id, 'nombre' => $r->nombre])->values(),
            ]);
    }

    public function create(array $data): User
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $usuario = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $usuario->roles()->sync($roles);

        return $usuario;
    }

    public function update(User $usuario, array $data): User
    {
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        if ($roles !== null) {
            $usuario->roles()->sync($roles);
        }

        return $usuario;
    }

    public function delete(User $usuario): void
    {
        $usuario->delete();
    }
}
