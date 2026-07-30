<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones;

use Illuminate\Foundation\Http\FormRequest;

class StoreCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folio' => ['required', 'string', 'max:100', 'unique:cotizaciones,folio'],
            'fecha' => ['required', 'date'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'vendedor' => ['nullable', 'string', 'max:255'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'moneda' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
