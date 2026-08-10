<?php

namespace App\Http\Requests\Ingenierias\Insumos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real ya la resuelve el middleware `permiso` en la ruta.
        return true;
    }

    public function rules(): array
    {
        return [
            'concepto' => ['sometimes', 'required', 'string', 'max:500'],
            'unidad' => ['sometimes', 'required', 'string', 'max:50'],
            'cantidad_presupuestada' => ['sometimes', 'required', 'numeric', 'min:0'],
            'precio' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
