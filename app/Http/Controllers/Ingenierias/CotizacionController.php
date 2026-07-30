<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Cotizaciones\StoreCotizacionRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\UpdateCotizacionRequest;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Planta;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CotizacionController extends Controller
{
    public function show(Planta $planta, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/levantamientos/cotizaciones/Show', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
            ],
            'levantamiento' => [
                'id' => $levantamiento->id,
                'folio' => $levantamiento->folio,
            ],
            'cotizacion' => $action->detail($cotizacion),
        ]);
    }

    public function store(StoreCotizacionRequest $request, Planta $planta, Levantamiento $levantamiento, CotizacionesAction $action): RedirectResponse
    {
        $action->create($levantamiento, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización creada.']);

        return back();
    }

    public function update(UpdateCotizacionRequest $request, Planta $planta, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->update($cotizacion, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización actualizada.']);

        return back();
    }

    public function destroy(Planta $planta, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->delete($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización eliminada.']);

        return back();
    }
}
