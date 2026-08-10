<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
