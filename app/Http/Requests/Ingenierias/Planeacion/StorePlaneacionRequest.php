<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaneacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semana' => ['required', 'integer', 'min:1', 'max:53'],
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'asignaciones' => ['nullable', 'array'],
            'asignaciones.*.partida_id' => ['required', 'integer', 'exists:partidas,id'],
            'asignaciones.*.empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'asignaciones.*.dia_semana' => ['required', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
        ];
    }
}
