<?php

namespace App\Http\Requests\Ingenierias\Insumos;

use App\Support\Ingenierias\Insumos\InsumoParserResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportInsumosRequest extends FormRequest
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
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'tipo_plantilla' => ['nullable', Rule::in(InsumoParserResolver::tiposDisponibles())],
        ];
    }
}
