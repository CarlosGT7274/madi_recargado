<?php

namespace App\Actions\Ingenierias\Actividades;

use App\Models\PlaneacionActividad;
use App\Models\Proyecto;
use Illuminate\Support\Collection;

/**
 * CRUD del árbol de actividades (equivalente a "partidas") de un Proyecto
 * directo. Vive en planeacion_actividades, con autorreferencia parent_id;
 * proyecto_id ancla el árbol al proyecto. No depende de Planeacion.
 */
class ActividadesAction
{
    public function arbol(Proyecto $proyecto): Collection
    {
        $raices = $proyecto->actividades()
            ->whereNull('parent_id')
            ->with('hijas')
            ->orderBy('id')
            ->get();

        return $raices->map(fn (PlaneacionActividad $a) => $this->nodo($a));
    }

    private function nodo(PlaneacionActividad $actividad): array
    {
        return [
            'id' => $actividad->id,
            'codigo' => $actividad->codigo,
            'nombre' => $actividad->nombre,
            'notas' => $actividad->notas,
            'hijas' => $actividad->hijas->map(fn (PlaneacionActividad $h) => $this->nodo($h))->all(),
        ];
    }

    public function create(Proyecto $proyecto, array $data): PlaneacionActividad
    {
        return $proyecto->actividades()->create($data);
    }

    public function update(PlaneacionActividad $actividad, array $data): PlaneacionActividad
    {
        $actividad->update($data);

        return $actividad;
    }

    public function delete(PlaneacionActividad $actividad): void
    {
        $actividad->delete();
    }
}
