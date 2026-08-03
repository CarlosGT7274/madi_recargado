<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones;

use App\Support\Ingenierias\Cotizaciones\CotizacionRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(CotizacionRules::rules(), [
            'archivo' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);
    }
}
