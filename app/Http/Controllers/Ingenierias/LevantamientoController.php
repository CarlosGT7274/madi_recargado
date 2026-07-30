<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\StoreLevantamientoRequest;
use App\Http\Requests\Ingenierias\UpdateLevantamientoRequest;
use App\Models\Levantamiento;
use App\Models\Planta;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LevantamientoController extends Controller
{
    public function index(Planta $planta, LevantamientosAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/levantamientos/Index', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
                'folio' => $planta->folio,
            ],
            'levantamientos' => $action->list($planta),
        ]);
    }

    public function data(Planta $planta, LevantamientosAction $action)
    {
        return response()->json($action->list($planta));
    }

    public function show(Planta $planta, Levantamiento $levantamiento, LevantamientosAction $action, CotizacionesAction $cotizacionesAction): Response
    {
        return Inertia::render('ingenierias/plantas/levantamientos/Show', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
            ],
            'levantamiento' => $action->detail($levantamiento),
            'cotizaciones' => Inertia::defer(
                fn () => $cotizacionesAction->list($levantamiento)
            ),
        ]);
    }

    public function store(StoreLevantamientoRequest $request, Planta $planta, LevantamientosAction $action): RedirectResponse
    {
        $action->create($planta, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento creado.']);

        return back();
    }

    public function update(UpdateLevantamientoRequest $request, Planta $planta, Levantamiento $levantamiento, LevantamientosAction $action): RedirectResponse
    {
        $action->update($levantamiento, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento actualizado.']);

        return back();
    }

    public function destroy(Planta $planta, Levantamiento $levantamiento, LevantamientosAction $action): RedirectResponse
    {
        $action->delete($levantamiento);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento eliminado.']);

        return back();
    }
}
