<?php

namespace App\Http\Requests\Ingenierias;

use App\Support\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLevantamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puede('Levantamientos', Accion::UPDATE) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folio' => [
                'required', 'string', 'max:100',
                Rule::unique('levantamientos', 'folio')->ignore($this->route('levantamiento')),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'cliente' => ['required', 'string', 'max:255'],
            'obra' => ['nullable', 'string', 'max:255'],
            'solicitante' => ['nullable', 'string', 'max:255'],
            'prioridad' => ['required', Rule::in(['urgente', 'normal', 'grande_compleja'])],
        ];
    }
}
