<?php

namespace App\Http\Requests\Ingenierias;

use App\Support\Ingenierias\LevantamientoRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLevantamientoRequest extends FormRequest
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
        return LevantamientoRules::rules($this->route('levantamiento')?->id);
    }
}
