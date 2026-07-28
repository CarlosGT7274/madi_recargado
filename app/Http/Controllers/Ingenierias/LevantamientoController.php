<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Actions\Ingenierias\Plantas\PlantasAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Levantamientos\StoreLevantamientoRequest;
use App\Http\Requests\Ingenierias\Levantamientos\UpdateLevantamientoRequest;
use App\Models\Levantamiento;
use App\Models\Planta;
use App\Support\Accion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Módulo Levantamientos (Ingenierías). Los levantamientos siempre cuelgan de
 * una planta, por eso las rutas están anidadas bajo `plantas/{planta}`.
 * Mantiene el mismo patrón que PlantaController: Controller -> Action.
 */
class LevantamientoController extends Controller
{
    public function show(Planta $planta, Levantamiento $levantamiento, LevantamientosAction $levantamientosAction, PlantasAction $plantasAction): Response
    {
        abort_unless($levantamiento->planta_id === $planta->id, 404);

        return Inertia::render('ingenierias/levantamientos/Show', [
            'planta' => $plantasAction->detail($planta),
            'levantamiento' => $levantamientosAction->detail($levantamiento),
        ]);
    }

    public function store(StoreLevantamientoRequest $request, Planta $planta, LevantamientosAction $levantamientosAction): RedirectResponse
    {
        $levantamientosAction->create($planta, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento creado.']);

        return back();
    }

    public function update(UpdateLevantamientoRequest $request, Planta $planta, Levantamiento $levantamiento, LevantamientosAction $levantamientosAction): RedirectResponse
    {
        abort_unless($levantamiento->planta_id === $planta->id, 404);

        $levantamientosAction->update($levantamiento, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento actualizado.']);

        return back();
    }

    public function destroy(Planta $planta, Levantamiento $levantamiento, LevantamientosAction $levantamientosAction): RedirectResponse
    {
        abort_unless($levantamiento->planta_id === $planta->id, 404);

        Gate::authorize('permiso', ['Levantamientos', Accion::DELETE]);

        $levantamientosAction->delete($levantamiento);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento eliminado.']);

        return to_route('ingenierias.plantas.show', $planta->id);
    }
}
