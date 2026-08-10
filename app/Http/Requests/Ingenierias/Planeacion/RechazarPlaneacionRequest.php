<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use Illuminate\Foundation\Http\FormRequest;

class RechazarPlaneacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentarios' => ['required', 'string', 'max:1000'],
        ];
    }
}
