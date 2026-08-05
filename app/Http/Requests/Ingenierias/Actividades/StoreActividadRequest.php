<?php

namespace App\Http\Requests\Ingenierias\Actividades;

use Illuminate\Foundation\Http\FormRequest;

class StoreActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:planeacion_actividades,id'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:500'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
