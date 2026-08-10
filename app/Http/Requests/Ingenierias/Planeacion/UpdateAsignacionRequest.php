<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dia_semana' => ['sometimes', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'estado' => ['sometimes', Rule::in(['asignado', 'en_progreso', 'completado', 'cancelado'])],
            'horas_trabajadas' => ['sometimes', 'numeric', 'min:0', 'max:24'],
            'horas_extra' => ['sometimes', 'numeric', 'min:0', 'max:24'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
