<?php

namespace App\Http\Requests\Ingenierias\Plantas;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantaRequest extends FormRequest
{
    /**
     * La autorización la resuelve el middleware `permiso` de la ruta contra el
     * árbol de permisos de la BD (acción CREATE sobre `ingenierias.plantas`).
     * Aquí no se duplica la lógica.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `folio` no se valida aquí: se genera en PlantasAction::create() vía
     * FolioService, igual que proyecto/levantamiento/cotización.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
        ];
    }
}
