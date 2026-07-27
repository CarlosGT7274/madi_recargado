<?php

namespace App\Http\Requests\Ingenierias\Plantas;

use App\Support\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puede('Plantas', Accion::CREATE) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folio' => ['required', 'string', 'max:100', Rule::unique('plantas', 'folio')],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
        ];
    }
}
