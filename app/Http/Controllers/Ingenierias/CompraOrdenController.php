<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Compras\CompraOrdenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Compras\SubirOrdenCompraRequest;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompraOrdenController extends Controller
{
    public function index(
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
    ): Response {
        $pdf = $cotizacion->ordenCompra?->pdf();

        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/orden-compra/Show', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre],
            'levantamiento' => ['id' => $levantamiento->id, 'folio' => $levantamiento->folio],
            'cotizacion' => [
                'id' => $cotizacion->id,
                'folio' => $cotizacion->folio,
                'total' => (float) $cotizacion->total,
                'tieneInsumos' => $cotizacion->tieneInsumos(),
            ],
            'ordenCompra' => $cotizacion->ordenCompra ? [
                'id' => $cotizacion->ordenCompra->id,
                'archivoId' => $pdf?->id,
                'pdfUrl' => $pdf?->urlPublica(),
                'pdfNombre' => $pdf?->nombre_archivo,
                'subidoEl' => $pdf?->fecha_creacion?->format('d/m/Y H:i'),
            ] : null,
        ]);
    }

    public function store(
        SubirOrdenCompraRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        CompraOrdenAction $action,
    ): RedirectResponse {
        $action->subirPdf($cotizacion, $request->file('archivo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Orden de compra subida.']);

        return back();
    }
}
