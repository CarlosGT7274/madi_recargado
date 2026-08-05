<?php

namespace App\Http\Requests\Ingenierias\Actividades;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:500'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
