<?php

namespace App\Actions\Ingenierias\Actividades;

use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
use App\Models\PlaneacionActividad;
use App\Models\Proyecto;
use Illuminate\Support\Collection;

/**
 * CRUD del árbol de actividades (equivalente a "partidas") de un Proyecto
 * directo. Las actividades capturadas a mano viven en
 * planeacion_actividades, con autorreferencia parent_id.
 *
 * Además, arbol() mezcla —sin duplicar storage— las Partidas de cualquier
 * cotización del proyecto que ya esté Cotizacion::estaCompletada(): esas
 * partidas representan el trabajo aprobado y deben verse aquí para
 * alimentar Planeación, igual que las actividades manuales.
 */
class ActividadesAction
{
    public function __construct(
        private readonly PartidasAction $partidasAction,
    ) {}

    public function arbol(Proyecto $proyecto): Collection
    {
        $manuales = $proyecto->actividades()
            ->whereNull('parent_id')
            ->with('hijas')
            ->orderBy('id')
            ->get()
            ->map(fn (PlaneacionActividad $a) => $this->nodo($a));

        $desdeCotizaciones = $this->arbolDesdeCotizaciones($proyecto);

        return $manuales->concat($desdeCotizaciones)->values();
    }

    private function arbolDesdeCotizaciones(Proyecto $proyecto): Collection
    {
        return $proyecto->cotizaciones()
            ->with('partidas', 'archivos', 'ordenCompra.archivos')
            ->get()
            ->filter(fn (Cotizacion $c) => $c->estaCompletada())
            ->flatMap(fn (Cotizacion $c) => $this->partidasAction->arbol($c))
            ->map(fn (array $raiz) => $this->nodoDesdePartida($raiz));
    }

    /**
     * Convierte un nodo de PartidasAction::arbol() (id/no/descripcion/hijas)
     * a la misma forma que nodo(). Usa ids negativos para no colisionar con
     * los ids (positivos) de planeacion_actividades: son tablas distintas,
     * ambas autoincrementales desde 1.
     *
     * @param  array<string, mixed>  $partida
     * @return array<string, mixed>
     */
    private function nodoDesdePartida(array $partida, bool $esHija = false): array
    {
        return [
            'id' => -1 * (int) $partida['id'],
            'codigo' => $esHija ? ($partida['no'] ?? null) : null,
            'nombre' => $partida['descripcion'],
            'notas' => null,
            'origen' => 'cotizacion',
            'hijas' => isset($partida['hijas'])
                ? collect($partida['hijas'])
                    ->map(fn (array $h) => $this->nodoDesdePartida($h, esHija: true))
                    ->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nodo(PlaneacionActividad $actividad): array
    {
        return [
            'id' => $actividad->id,
            'codigo' => $actividad->codigo,
            'nombre' => $actividad->nombre,
            'notas' => $actividad->notas,
            'origen' => 'manual',
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
