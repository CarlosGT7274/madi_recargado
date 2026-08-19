<?php

namespace App\Support\Ingenierias\Cotizaciones;

use Illuminate\Validation\Rule;

class CotizacionRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'para' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'obra' => ['nullable', 'string', 'max:255'],
            'vendedor' => ['nullable', 'string', 'max:255'],
            'correo_vendedor' => ['nullable', 'email', 'max:255'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'tiempo_entrega' => ['nullable', 'string', 'max:100'],
            'dias_credito' => ['nullable', 'string', 'max:255'],
            'vigencia_cotizacion' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', Rule::in(['borrador', 'enviada', 'aprobada', 'rechazada'])],
        ];
    }
}
