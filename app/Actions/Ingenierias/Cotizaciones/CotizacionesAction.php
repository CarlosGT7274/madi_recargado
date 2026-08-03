<?php

namespace App\Actions\Ingenierias\Cotizaciones;

use App\Models\Cotizacion;
use App\Models\Levantamiento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CotizacionesAction
{
    public function list(Levantamiento $levantamiento): Collection
    {
        return $levantamiento->cotizaciones()
            ->latest('id')
            ->get()
            ->map(fn (Cotizacion $c) => [
                'id' => $c->id,
                'folio' => $c->folio,
                'fecha' => $c->fecha?->format('d/m/Y'),
                'cliente' => $c->cliente,
                'vendedor' => $c->vendedor,
                'total' => $c->total,
                'estado' => $c->estado,
            ]);
    }

    public function detail(Cotizacion $cotizacion): array
    {
        return [
            'id' => $cotizacion->id,
            'levantamiento_id' => $cotizacion->levantamiento_id,
            'folio' => $cotizacion->folio,
            'fecha' => $cotizacion->fecha?->format('d/m/Y'),
            'para' => $cotizacion->para,
            'cliente' => $cotizacion->cliente,
            'direccion' => $cotizacion->direccion,
            'obra' => $cotizacion->obra,
            'vendedor' => $cotizacion->vendedor,
            'proveedor' => $cotizacion->proveedor,
            'correo_vendedor' => $cotizacion->correo_vendedor,
            'subtotal' => $cotizacion->subtotal,
            'iva' => $cotizacion->iva,
            'total' => $cotizacion->total,
            'moneda' => $cotizacion->moneda,
            'tiempo_entrega' => $cotizacion->tiempo_entrega,
            'dias_credito' => $cotizacion->dias_credito,
            'vigencia_cotizacion' => $cotizacion->vigencia_cotizacion,
            'notas' => $cotizacion->notas,
            'estado' => $cotizacion->estado,
            'creado' => $cotizacion->created_at?->format('d/m/Y H:i'),
            'modificado' => $cotizacion->updated_at?->format('d/m/Y H:i'),
        ];
    }

    public function create(Levantamiento $levantamiento, array $data): Cotizacion
    {
        return $levantamiento->cotizaciones()->create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function update(Cotizacion $cotizacion, array $data): Cotizacion
    {
        $cotizacion->update($data);

        return $cotizacion;
    }

    public function delete(Cotizacion $cotizacion): void
    {
        $cotizacion->delete();
    }

    public function recalcularTotales(Cotizacion $cotizacion): void
    {
        $subtotal = (float) $cotizacion->partidas()->sum('importe');
        $costoHoraTotal = (float) $cotizacion->partidas()
            ->selectRaw('SUM(costo_hora * cantidad) as total')
            ->value('total');

        $cotizacion->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + (float) $cotizacion->iva, // iva se queda como esté, se captura a mano
            'costo_hora_total' => $costoHoraTotal ?? 0,
            'tiene_partidas' => $cotizacion->partidas()->exists(),
        ]);
    }
}
