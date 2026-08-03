<?php

namespace App\Http\Requests\Ingenierias\Proyectos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProyectoRequest extends FormRequest
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
            'tipo' => ['required', Rule::in(['grande', 'chico'])],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'max:255'],
            'bloqueado' => ['nullable', 'boolean'],
            'motivo_bloqueo' => ['nullable', 'string'],
        ];
    }
}
