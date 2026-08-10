<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Actividades\ActividadesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Actividades\StoreActividadRequest;
use App\Http\Requests\Ingenierias\Actividades\UpdateActividadRequest;
use App\Models\Partida;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class ActividadController extends Controller
{
    /**
     * Lista plana (sin árbol) de las actividades hoja del proyecto, para
     * poblar las filas del calendario semanal de Planeación. Las
     * categorías (nodos con hijas) no son asignables por sí solas —
     * solo sus hojas, con la categoría concatenada como contexto.
     */
    public function data(Planta $planta, Proyecto $proyecto, ActividadesAction $action): JsonResponse
    {
        return response()->json($this->filasPlanas($action->arbol($proyecto))->values());
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $nodos
     * @return Collection<int, array{id: int, descripcion: string}>
     */
    private function filasPlanas(Collection $nodos, string $prefijo = ''): Collection
    {
        return $nodos->flatMap(function (array $nodo) use ($prefijo) {
            $etiqueta = $prefijo === '' ? $nodo['nombre'] : "{$prefijo} · {$nodo['nombre']}";

            if (empty($nodo['hijas'])) {
                return [['id' => $nodo['id'], 'descripcion' => $etiqueta]];
            }

            return $this->filasPlanas(collect($nodo['hijas']), $etiqueta);
        });
    }

    public function store(StoreActividadRequest $request, Planta $planta, Proyecto $proyecto, ActividadesAction $action): RedirectResponse
    {
        $action->create($proyecto, $request->validated());

        return back();
    }

    public function update(UpdateActividadRequest $request, Planta $planta, Proyecto $proyecto, Partida $actividad, ActividadesAction $action): RedirectResponse
    {
        $action->update($actividad, $request->validated());

        return back();
    }

    public function destroy(Planta $planta, Proyecto $proyecto, Partida $actividad, ActividadesAction $action): RedirectResponse
    {
        $action->delete($actividad);

        return back();
    }
}
