<?php

namespace App\Http\Requests\Ingenierias;

use App\Support\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLevantamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puede('Levantamientos', Accion::CREATE) ?? false;
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
