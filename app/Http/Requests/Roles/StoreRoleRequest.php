<?php

namespace App\Http\Requests\Roles;

use App\Support\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puede('Roles', Accion::CREATE) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('roles', 'nombre')],
            'activo' => ['boolean'],
        ];
    }
}
