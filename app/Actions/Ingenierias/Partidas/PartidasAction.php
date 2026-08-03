<?php

namespace App\Actions\Ingenierias\Partidas;

use App\Models\Cotizacion;
use App\Models\Partida;
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

    public function create(Cotizacion $cotizacion, array $data): Partida
    {
        $data['numero_partida'] = $data['numero_partida']
            ?? (($cotizacion->partidas()->max('numero_partida') ?? 0) + 1);
        $data['importe'] = round($data['cantidad'] * $data['precio_unitario'], 2);

        $partida = $cotizacion->partidas()->create($data);
        $this->recalcularTotales($cotizacion);

        return $partida;
    }

    public function update(Partida $partida, array $data): Partida
    {
        $cantidad = $data['cantidad'] ?? $partida->cantidad;
        $precio = $data['precio_unitario'] ?? $partida->precio_unitario;
        $data['importe'] = round($cantidad * $precio, 2);

        $partida->update($data);
        $this->recalcularTotales($partida->cotizacion);

        return $partida;
    }

    public function delete(Partida $partida): void
    {
        $cotizacion = $partida->cotizacion;
        $partida->delete();
        $this->recalcularTotales($cotizacion);
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
}
