<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Plantas\PlantasAction;
use App\Actions\Ingenierias\Proyectos\ProyectosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Plantas\StorePlantaRequest;
use App\Http\Requests\Ingenierias\Plantas\UpdatePlantaRequest;
use App\Models\Planta;
use Illuminate\Http\RedirectResponse;
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

    public function show(Planta $planta, PlantasAction $plantasAction, ProyectosAction $proyectosAction): Response
    {
        return Inertia::render('ingenierias/plantas/Show', [
            'planta' => $plantasAction->detail($planta),
            'proyectos' => Inertia::defer(
                fn () => $proyectosAction->list($planta)
            ),
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
        $plantasAction->delete($planta);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planta eliminada.']);

        return redirect()->route('ingenierias.plantas.index');
    }
}
