<?php

namespace App\Actions\Ingenierias\Insumos;

use App\Models\Cotizacion;
use App\Models\Insumo;
use Illuminate\Support\Collection;

class InsumosAction
{
    public function list(Cotizacion $cotizacion): Collection
    {
        return $cotizacion->insumos()
            ->where('activo', true)
            ->orderBy('categoria')
            ->orderBy('codigo')
            ->get()
            ->map(fn (Insumo $i) => [
                'id' => $i->id,
                'codigo' => $i->codigo,
                'concepto' => $i->concepto,
                'unidad' => $i->unidad,
                'categoria' => $i->categoria,
                'cantidad' => (float) $i->cantidad_presupuestada,
                'precio' => (float) $i->precio > 0 ? (float) $i->precio : null,
                'importe' => (float) $i->importe,
                'estatus' => $i->estatus,
            ]);
    }

    /** @return array<string, mixed> */
    public function resumen(Cotizacion $cotizacion): array
    {
        $insumos = $cotizacion->insumos()->where('activo', true)->get();

        $subtotal = (float) $insumos->sum('importe');
        $iva = round($subtotal * 0.16, 2);
        $totalConIva = $subtotal + $iva;
        $totalCotizacion = (float) $cotizacion->total;
        $utilidadEstimada = $totalCotizacion - $totalConIva;
        $margenEstimado = $totalCotizacion > 0
            ? round(($utilidadEstimada / $totalCotizacion) * 100, 1)
            : null;

        return [
            'total' => $insumos->count(),
            'materiales' => $insumos->where('categoria', 'materiales')->count(),
            'manoObra' => $insumos->where('categoria', 'mano_obra')->count(),
            'maquinaria' => $insumos->where('categoria', 'maquinaria')->count(),
            'requisitados' => $insumos->where('estatus', 'requisitado')->count(),
            'subtotal' => $subtotal,
            'iva' => $iva,
            'totalConIva' => $totalConIva,
            'totalCotizacion' => $totalCotizacion,
            'utilidadEstimada' => $utilidadEstimada,
            'margenEstimado' => $margenEstimado,
        ];
    }

    public function delete(Insumo $insumo): void
    {
        $insumo->delete();
    }
}
