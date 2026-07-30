<?php

namespace App\Http\Requests\Ingenierias\Plantas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantaRequest extends FormRequest
{
    /**
     * La autorización la resuelve el middleware `permiso` de la ruta contra el
     * árbol de permisos de la BD (acción CREATE sobre `ingenierias.plantas`).
     * Aquí no se duplica la lógica.
     */
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
            'folio' => ['required', 'string', 'max:100', Rule::unique('plantas', 'folio')],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
        ];
    }
}
