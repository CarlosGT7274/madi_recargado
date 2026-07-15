<?php

namespace App\Http\Requests\Roles;

use App\Models\Permiso;
use App\Support\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRolePermisosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puede('Roles', Accion::UPDATE) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'permisos' => ['present', 'array'],
            'permisos.*' => ['integer', 'between:0,15'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $idsValidos = Permiso::pluck('id')->all();

                foreach (array_keys($this->input('permisos', [])) as $permisoId) {
                    if (! in_array((int) $permisoId, $idsValidos, true)) {
                        $validator->errors()->add('permisos', "El permiso [{$permisoId}] no existe.");
                    }
                }
            },
        ];
    }
}
