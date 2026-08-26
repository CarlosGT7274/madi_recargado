<?php

namespace App\Http\Requests\Ingenierias\Planeacion;

use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCompromisoEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedePorEndpoint('ingenierias.planeacion', 'supervisar') ?? false;
    }

    public function rules(): array
    {
        return [
            'planta_id' => ['nullable', 'integer', 'exists:plantas,id'],
            'proyecto_id' => ['nullable', 'integer', 'exists:proyectos,id'],
            'fecha' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Un proyecto siempre implica su planta: si mandan proyecto_id sin
     * planta_id (o con una que no le corresponde), lo resolvemos aquí
     * para no depender de que el frontend lo mande bien armado.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('proyecto_id') && ! $this->filled('planta_id')) {
            $plantaId = Proyecto::whereKey($this->input('proyecto_id'))->value('planta_id');
            $this->merge(['planta_id' => $plantaId]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $proyectoId = $this->input('proyecto_id');
            $plantaId = $this->input('planta_id');

            if ($proyectoId !== null && $plantaId !== null) {
                $perteneceAPlanta = Proyecto::whereKey($proyectoId)
                    ->where('planta_id', $plantaId)
                    ->exists();

                if (! $perteneceAPlanta) {
                    $validator->errors()->add('proyecto_id', 'El proyecto seleccionado no pertenece a la planta seleccionada.');
                }
            }
        });
    }
}
