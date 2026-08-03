<?php

namespace App\Http\Requests\Ingenierias;

use App\Support\Ingenierias\LevantamientoRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreLevantamientoRequest extends FormRequest
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
        return LevantamientoRules::rules(ignorarId: null, incluirFolio: false);
    }
}
