<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCronogramaPlaneacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enviar_aprobacion' => ['sometimes', 'boolean'],
            'nota' => ['nullable', 'string', 'max:1000'],
            'asignaciones' => ['nullable', 'array'],
            'asignaciones.*.partida_id' => ['required', 'integer', 'exists:partidas,id'],
            'asignaciones.*.empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'asignaciones.*.dia_semana' => ['required', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'asignaciones.*.horas_trabajadas' => ['required', 'numeric', 'min:0.5', 'max:24'],
        ];
    }
}
