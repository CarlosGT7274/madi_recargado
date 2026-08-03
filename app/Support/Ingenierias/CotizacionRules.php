<?php

namespace App\Support\Ingenierias;

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
            'proveedor' => ['nullable', 'string', 'max:255'],
            'correo_vendedor' => ['nullable', 'email', 'max:255'],
            'moneda' => ['nullable', 'string', 'max:50'],
            'tiempo_entrega' => ['nullable', 'string', 'max:100'],
            'dias_credito' => ['nullable', 'string', 'max:50'],
            'vigencia_cotizacion' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            // iva: monto libre, lo captura la persona; no se deriva del subtotal
            'iva' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
