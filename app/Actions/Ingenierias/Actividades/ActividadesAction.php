<?php

namespace App\Actions\Ingenierias\Actividades;

use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Partida;
use App\Models\Proyecto;
use Illuminate\Support\Collection;

/**
 * Árbol de "actividades" de un Proyecto directo. Desde el merge de
 * 2026-08-10, Partida es la ÚNICA fuente de verdad: una actividad manual
 * es una Partida con cotizacion_id NULL; una actividad que viene de una
 * cotización aprobada es una Partida con cotizacion_id set. Ya no existe
 * ninguna tabla/modelo separado para actividades — este Action solo arma
 * el árbol de visualización y delega la escritura a PartidasAction.
 *
 * Solo se muestran como "actividad" las partidas manuales (siempre) y las
 * de cotizaciones que ya estén Cotizacion::estaCompletada() (representan
 * trabajo aprobado, listo para alimentar Planeación).
 */
class ActividadesAction
{
    public function __construct(
        private readonly PartidasAction $partidasAction,
    ) {}

    public function arbol(Proyecto $proyecto): Collection
    {
        return $proyecto->partidas()
            ->whereNull('partida_id')
            ->with(['hijas', 'cotizacion.archivos'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Partida $p) => $p->esManual() || $p->cotizacion?->estaCompletada())
            ->map(fn (Partida $p) => $this->nodo($p))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function nodo(Partida $partida): array
    {
        return [
            'id' => $partida->id,
            'codigo' => $partida->numero_partida,
            'nombre' => $partida->descripcion,
            'notas' => $partida->notas,
            'origen' => $partida->esManual() ? 'manual' : 'cotizacion',
            'hijas' => $partida->hijas->map(fn (Partida $h) => $this->nodo($h))->all(),
        ];
    }

    public function create(Proyecto $proyecto, array $data): Partida
    {
        return $this->partidasAction->createManual($proyecto, $data);
    }

    public function update(Partida $actividad, array $data): Partida
    {
        return $this->partidasAction->update($actividad, $data);
    }

    public function delete(Partida $actividad): void
    {
        $this->partidasAction->delete($actividad);
    }
}
