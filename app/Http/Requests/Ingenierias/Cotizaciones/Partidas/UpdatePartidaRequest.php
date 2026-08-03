<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones\Partidas;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartidaRequest extends FormRequest
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
            'numero_partida' => ['nullable', 'integer', 'min:1'],
            'descripcion' => ['required', 'string'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'unidad' => ['nullable', 'string', 'max:50'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'costo_hora' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
