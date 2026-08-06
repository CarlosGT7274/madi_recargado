<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones;

use App\Support\Ingenierias\Cotizaciones\CotizacionRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCotizacionManualRequest extends FormRequest
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
        return array_merge(CotizacionRules::rules(), [
            'categorias' => ['required', 'array', 'min:1'],
            'categorias.*.descripcion' => ['required', 'string', 'max:500'],
            'categorias.*.partidas' => ['required', 'array', 'min:1'],
            'categorias.*.partidas.*.descripcion' => ['required', 'string'],
            'categorias.*.partidas.*.unidad' => ['nullable', 'string', 'max:50'],
            'categorias.*.partidas.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'categorias.*.partidas.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'categorias.*.partidas.*.costo_hora' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
