<?php

namespace App\Http\Requests\Seguridad\Usuarios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $esUsuario = $this->input('tipo') === 'usuario';

        return [
            'tipo' => ['required', Rule::in(['usuario', 'empleado'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => Rule::when($esUsuario, ['required', 'email', 'max:255', 'unique:users,email']),
            // 'confirmed' exige un campo `password_confirmation` con el
            // mismo valor. Si el form no lo manda, esta regla SIEMPRE
            // falla — eso era lo que impedía crear usuarios.
            'password' => Rule::when($esUsuario, ['required', 'confirmed', Password::defaults()]),
            'rol_id' => ['nullable', 'integer', 'exists:roles,id'],
            'puesto' => ['nullable', 'string', 'max:255'],
        ];
    }
}
