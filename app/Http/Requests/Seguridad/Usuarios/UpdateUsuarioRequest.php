<?php

namespace App\Http\Requests\Seguridad\Usuarios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('usuario'))],
            // nullable: dejar en blanco = no cambiar contraseña. Si se
            // manda algo, exige password_confirmation igual que al crear.
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'rol_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }
}
