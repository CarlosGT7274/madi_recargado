<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstatusCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'estatus_compra' => [
                'required',
                Rule::in(['pendiente', 'en_cotizacion', 'aprobado', 'rechazado']),
            ],
        ];
    }
}
