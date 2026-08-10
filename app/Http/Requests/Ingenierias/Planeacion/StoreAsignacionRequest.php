<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partida_id' => ['required', 'integer', 'exists:partidas,id'],
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'dia_semana' => ['required', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'horas_trabajadas' => ['required', 'numeric', 'min:0', 'max:24'],
            'horas_extra' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ];
    }
}
