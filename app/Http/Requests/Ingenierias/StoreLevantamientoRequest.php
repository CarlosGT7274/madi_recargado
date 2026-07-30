<?php

namespace App\Http\Requests\Ingenierias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLevantamientoRequest extends FormRequest
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
            'folio' => ['required', 'string', 'max:100', Rule::unique('levantamientos', 'folio')],
            'nombre' => ['required', 'string', 'max:255'],
            'cliente' => ['required', 'string', 'max:255'],
            'obra' => ['nullable', 'string', 'max:255'],
            'solicitante' => ['nullable', 'string', 'max:255'],
            'prioridad' => ['required', Rule::in(['urgente', 'normal', 'grande_compleja'])],
        ];
    }
}
