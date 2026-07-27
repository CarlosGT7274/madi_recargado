<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Plantas\PlantasAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Plantas\StorePlantaRequest;
use App\Http\Requests\Ingenierias\Plantas\UpdatePlantaRequest;
use App\Models\Planta;
use App\Support\Accion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlantaController extends Controller
{
    public function index(PlantasAction $plantasAction): Response
    {
        return Inertia::render('ingenierias/plantas/Index', [
            'plantas' => $plantasAction->list(),
        ]);
    }

    public function show(Planta $planta, PlantasAction $plantasAction): Response
    {
        return Inertia::render('ingenierias/plantas/Show', [
            'planta' => $plantasAction->detail($planta),
        ]);
    }

    public function store(StorePlantaRequest $request, PlantasAction $plantasAction): RedirectResponse
    {
        $plantasAction->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planta creada.']);

        return back();
    }

    public function update(UpdatePlantaRequest $request, Planta $planta, PlantasAction $plantasAction): RedirectResponse
    {
        $plantasAction->update($planta, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planta actualizada.']);

        return back();
    }

    public function destroy(Planta $planta, PlantasAction $plantasAction): RedirectResponse
    {
        Gate::authorize('permiso', ['Plantas', Accion::DELETE]);

        $plantasAction->delete($planta);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planta eliminada.']);

        return back();
    }
}
