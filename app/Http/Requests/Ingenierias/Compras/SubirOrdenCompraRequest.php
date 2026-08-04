<?php

namespace App\Http\Requests\Ingenierias\Compras;

use Illuminate\Foundation\Http\FormRequest;

class SubirOrdenCompraRequest extends FormRequest
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
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
