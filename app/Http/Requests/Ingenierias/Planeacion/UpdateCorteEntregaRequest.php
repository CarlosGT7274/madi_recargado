<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCorteEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'corte_dia_semana' => ['nullable', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'corte_hora' => ['nullable', 'date_format:H:i'],
            'corte_semana_relativa' => ['required', Rule::in(['actual', 'anterior'])],
        ];
    }
}
