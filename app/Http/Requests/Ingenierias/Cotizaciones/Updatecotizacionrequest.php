<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cotizacion = $this->route('cotizacion');

        return [
            'folio' => ['required', 'string', 'max:100', Rule::unique('cotizaciones', 'folio')->ignore($cotizacion?->id)],
            'fecha' => ['required', 'date'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'vendedor' => ['nullable', 'string', 'max:255'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'moneda' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string'],
            'estado' => ['nullable', Rule::in(['borrador', 'enviada', 'aprobada', 'rechazada'])],
        ];
    }
}
