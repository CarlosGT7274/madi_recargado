<?php

namespace App\Actions\Ingenierias\Cotizaciones\Partidas;

use App\Models\Cotizacion;
use App\Models\Partida;
use App\Models\Proyecto;
use Illuminate\Support\Collection;

class PartidasAction
{
    public function list(Cotizacion $cotizacion): Collection
    {
        return $cotizacion->partidas()->get()->map(fn (Partida $p) => [
            'id' => $p->id,
            'numeroPartida' => $p->numero_partida,
            'descripcion' => $p->descripcion,
            'cantidad' => (float) $p->cantidad,
            'unidad' => $p->unidad,
            'precioUnitario' => (float) $p->precio_unitario,
            'importe' => (float) $p->importe,
            'costoHora' => $p->costo_hora !== null ? (float) $p->costo_hora : null,
        ]);
    }

    /**
     * Crea una partida colgada de una Cotización. `proyecto_id` se
     * denormaliza SIEMPRE desde la cotización — es el invariante que
     * permite a Proyecto::partidas() (y por lo tanto Planeación) ver
     * todas las partidas del proyecto sin importar su origen.
     */
    public function create(Cotizacion $cotizacion, array $data): Partida
    {
        $data['numero_partida'] = $data['numero_partida']
            ?? (($cotizacion->partidas()->max('numero_partida') ?? 0) + 1);
        $data['importe'] = round($data['cantidad'] * $data['precio_unitario'], 2);
        $data['proyecto_id'] = $cotizacion->proyecto_id;

        $partida = $cotizacion->partidas()->create($data);
        $this->recalcularTotales($cotizacion);

        return $partida;
    }

    /**
     * Crea una partida manual (actividad de Proyecto directo, sin pasar
     * por una cotización). cotizacion_id queda NULL a propósito.
     */
    public function createManual(Proyecto $proyecto, array $data): Partida
    {
        $data['numero_partida'] = $data['numero_partida']
            ?? (($proyecto->partidasManuales()->whereNull('partida_id')->max('numero_partida') ?? 0) + 1);
        $data['cotizacion_id'] = null;
        $data['cantidad'] = $data['cantidad'] ?? 0;
        $data['precio_unitario'] = $data['precio_unitario'] ?? 0;
        $data['importe'] = round($data['cantidad'] * $data['precio_unitario'], 2);

        return $proyecto->partidas()->create($data);
    }

    public function update(Partida $partida, array $data): Partida
    {
        $cantidad = $data['cantidad'] ?? $partida->cantidad;
        $precio = $data['precio_unitario'] ?? $partida->precio_unitario;
        $data['importe'] = round($cantidad * $precio, 2);

        $partida->update($data);

        // Una partida manual no tiene cotización que recalcular.
        if ($partida->cotizacion !== null) {
            $this->recalcularTotales($partida->cotizacion);
        }

        return $partida;
    }

    public function delete(Partida $partida): void
    {
        $cotizacion = $partida->cotizacion;
        $partida->delete();

        if ($cotizacion !== null) {
            $this->recalcularTotales($cotizacion);
        }
    }

    public function recalcularTotales(Cotizacion $cotizacion): void
    {
        $subtotal = (float) $cotizacion->partidas()->sum('importe');
        $costoHoraTotal = (float) ($cotizacion->partidas()
            ->selectRaw('SUM(costo_hora * cantidad) as total')
            ->value('total') ?? 0);

        $cotizacion->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + (float) $cotizacion->iva,
            'costo_hora_total' => $costoHoraTotal,
            'tiene_partidas' => $cotizacion->partidas()->exists(),
        ]);
    }

    public function arbol(Cotizacion $cotizacion): array
    {
        $raices = $cotizacion->partidas()
            ->whereNull('partida_id')
            ->orderBy('numero_partida')
            ->with('hijas')
            ->get();

        return $raices->map(fn (Partida $padre) => [
            'id' => $padre->id,
            'no' => (string) $padre->numero_partida,
            'descripcion' => $padre->descripcion,
            'hijas' => $padre->hijas->map(fn (Partida $h) => [
                'id' => $h->id,
                'no' => "{$padre->numero_partida}.{$h->numero_partida}",
                'descripcion' => $h->descripcion,
                'unidad' => $h->unidad,
                'cantidad' => (float) $h->cantidad,
                'precioUnitario' => (float) $h->precio_unitario,
                'importe' => (float) $h->importe,
            ])->all(),
        ])->all();
    }

    /**
     * Partidas hoja de una cotización, aplanadas y con la categoría padre
     * como contexto — mismo criterio que ActividadController::filasPlanas()
     * usa para poblar el calendario de Planeación. Estas son las
     * "actividades disponibles" una vez que se elige una cotización
     * aprobada en el flujo de creación de Planeación.
     */
    public function disponibles(Cotizacion $cotizacion): Collection
    {
        return collect($this->arbol($cotizacion))
            ->flatMap(fn (array $raiz) => collect($raiz['hijas'])->map(fn (array $h) => [
                'id' => $h['id'],
                'descripcion' => "{$raiz['descripcion']} · {$h['descripcion']}",
                'unidad' => $h['unidad'],
                'cantidad' => $h['cantidad'],
            ]))
            ->values();
    }
}
