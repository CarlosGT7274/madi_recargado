<?php

namespace App\Actions\Ingenierias\Actividades;

use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
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
        $cotizacionIdsValidos = $this->ultimasCotizacionesAprobadasPorObra($proyecto)
            ->pluck('id');

        return $proyecto->partidas()
            ->whereNull('partida_id')
            ->with(['hijas', 'cotizacion.archivos'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Partida $p) => $p->esManual() || $cotizacionIdsValidos->contains($p->cotizacion_id))
            ->map(fn (Partida $p) => $this->nodo($p))
            ->values();
    }

    /**
     * SOLO efecto visual: para cada obra del proyecto (agrupando igual
     * que CotizacionesAction::agruparPorObra(), con NULL como su propio
     * grupo), toma únicamente la cotización más reciente (mayor id) que
     * ya esté Cotizacion::estaCompletada(). No modifica Cotizacion, no
     * escribe nada, no cambia estados — solo decide qué partidas se
     * muestran en el árbol de Actividades quitando versiones viejas ya
     * superadas dentro de la MISMA obra. Cotizaciones aprobadas de OTRAS
     * obras siguen apareciendo todas.
     *
     * @return Collection<int, Cotizacion>
     */
    private function ultimasCotizacionesAprobadasPorObra(Proyecto $proyecto): Collection
    {
        return $proyecto->cotizaciones()
            ->with('archivos')
            ->latest('id')
            ->get()
            ->filter(fn (Cotizacion $c) => $c->estaCompletada())
            ->groupBy(fn (Cotizacion $c) => $c->obra ?? "\0__sin_obra__{$c->id}")
            ->map(fn (Collection $versiones) => $versiones->first())
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
