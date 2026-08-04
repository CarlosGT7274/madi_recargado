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

class CompraOrdenController extends Controller
{
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
