<?php

namespace App\Http\Requests\Ingenierias;

use Illuminate\Foundation\Http\FormRequest;

class ImportLevantamientosRequest extends FormRequest
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
        ];
    }
}
