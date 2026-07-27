<?php

namespace App\Actions\Ingenierias\Plantas;

use App\Models\Planta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Agrupa todas las operaciones CRUD del módulo Plantas (Ingenierías),
 * siguiendo el mismo patrón que RolesAction: una única Action por
 * contexto, sin capa de Services ni Domain.
 */
class PlantasAction
{
    /** Listar todas las plantas */
    public function list(): Collection
    {
        return Planta::query()
            ->latest('id')
            ->get()
            ->map(fn (Planta $planta) => [
                'id' => $planta->id,
                'folio' => $planta->folio,
                'nombre' => $planta->nombre,
                'direccion' => $planta->direccion,
                'activa' => $planta->activa,
                'creada' => $planta->fecha_creacion
                    ? Carbon::parse($planta->fecha_creacion)->format('d/m/Y')
                    : null,
            ]);
    }

    /** Obtener detalle de una planta */
    public function detail(Planta $planta): array
    {
        return [
            'id' => $planta->id,
            'folio' => $planta->folio,
            'nombre' => $planta->nombre,
            'direccion' => $planta->direccion,
            'descripcion' => $planta->descripcion,
            'activa' => $planta->activa,
            'creada' => $planta->fecha_creacion
                ? Carbon::parse($planta->fecha_creacion)->format('d/m/Y H:i')
                : null,
            'modificada' => $planta->fecha_modificacion
                ? Carbon::parse($planta->fecha_modificacion)->format('d/m/Y H:i')
                : null,
        ];
    }

    /** Crear una nueva planta */
    public function create(array $data): Planta
    {
        return Planta::create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    /** Actualizar una planta existente */
    public function update(Planta $planta, array $data): Planta
    {
        $planta->update($data);

        return $planta;
    }

    /** Eliminar una planta */
    public function delete(Planta $planta): void
    {
        $planta->delete();
    }
}
