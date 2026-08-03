<?php

namespace App\Http\Requests\Ingenierias\Cotizaciones;

use App\Support\Ingenierias\CotizacionRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return CotizacionRules::rules();
    }
}
