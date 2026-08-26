<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCorteEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'compromiso_fecha' => ['nullable', 'date'],
            'compromiso_nota' => ['nullable', 'string', 'max:500'],
        ];
    }
}
