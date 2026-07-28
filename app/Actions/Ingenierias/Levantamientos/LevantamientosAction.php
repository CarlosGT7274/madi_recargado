<?php

namespace App\Actions\Ingenierias\Levantamientos;

use App\Models\Levantamiento;
use App\Models\Planta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Agrupa todas las operaciones CRUD del módulo Levantamientos (Ingenierías),
 * siguiendo el mismo patrón que PlantasAction: una única Action por
 * contexto, sin capa de Services ni Domain. Los levantamientos siempre
 * viven dentro de una planta (relación planta_id).
 *
 * NOTA (demo): aquí solo vive el esqueleto CRUD. La lógica de negocio real
 * (estatus, cotizaciones, permisos de trabajo, fechas programadas, etc.) se
 * agregará más adelante sobre esta misma estructura.
 */
class LevantamientosAction
{
    /** Listar los levantamientos de una planta */
    public function listByPlanta(Planta $planta): Collection
    {
        return $planta->levantamientos()
            ->latest('id')
            ->get()
            ->map(fn (Levantamiento $levantamiento) => [
                'id' => $levantamiento->id,
                'folio' => $levantamiento->folio,
                'nombre' => $levantamiento->nombre,
                'cliente' => $levantamiento->cliente,
                'area_trabajo' => $levantamiento->area_trabajo,
                'prioridad' => $levantamiento->prioridad,
                'estatus_admin' => $levantamiento->estatus_admin,
                'creado' => $levantamiento->fecha_creacion
                    ? Carbon::parse($levantamiento->fecha_creacion)->format('d/m/Y')
                    : null,
            ]);
    }

    /** Obtener el detalle de un levantamiento */
    public function detail(Levantamiento $levantamiento): array
    {
        return [
            'id' => $levantamiento->id,
            'planta_id' => $levantamiento->planta_id,
            'folio' => $levantamiento->folio,
            'nombre' => $levantamiento->nombre,
            'cliente' => $levantamiento->cliente,
            'obra' => $levantamiento->obra,
            'solicitante' => $levantamiento->solicitante,
            'area_trabajo' => $levantamiento->area_trabajo,
            'prioridad' => $levantamiento->prioridad,
            'medio_solicitud' => $levantamiento->medio_solicitud,
            'estatus_admin' => $levantamiento->estatus_admin,
            'notas_admin' => $levantamiento->notas_admin,
            'creado' => $levantamiento->fecha_creacion
                ? Carbon::parse($levantamiento->fecha_creacion)->format('d/m/Y H:i')
                : null,
            'modificado' => $levantamiento->fecha_modificacion
                ? Carbon::parse($levantamiento->fecha_modificacion)->format('d/m/Y H:i')
                : null,
        ];
    }

    /** Crear un nuevo levantamiento dentro de una planta */
    public function create(Planta $planta, array $data): Levantamiento
    {
        return $planta->levantamientos()->create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    /** Actualizar un levantamiento existente */
    public function update(Levantamiento $levantamiento, array $data): Levantamiento
    {
        $levantamiento->update($data);

        return $levantamiento;
    }

    /** Eliminar un levantamiento */
    public function delete(Levantamiento $levantamiento): void
    {
        $levantamiento->delete();
    }
}
