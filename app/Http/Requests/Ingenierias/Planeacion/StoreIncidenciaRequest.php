<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['falta', 'vacaciones', 'cambio_dia', 'movimiento', 'enfermedad', 'horas_extra', 'otro'])],
            'dia_anterior' => ['nullable', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'dia_nuevo' => ['nullable', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'horas_extra' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'fecha' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
