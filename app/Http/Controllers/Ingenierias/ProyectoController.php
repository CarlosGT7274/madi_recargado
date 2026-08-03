<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Actions\Ingenierias\Proyectos\ProyectosAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Proyectos\StoreProyectoRequest;
use App\Http\Requests\Ingenierias\Proyectos\UpdateProyectoRequest;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoController extends Controller
{
    public function create(Planta $planta): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/Create', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
        ]);
    }

    public function show(Planta $planta, Proyecto $proyecto, ProyectosAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/Show', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => $action->detail($proyecto),
            'levantamientos' => Inertia::defer(
                fn () => app(LevantamientosAction::class)->list($proyecto)
            ),
        ]);
    }

    public function store(StoreProyectoRequest $request, Planta $planta, ProyectosAction $action): RedirectResponse
    {
        $proyecto = $action->create($planta, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyecto creado.']);

        return redirect()->route('ingenierias.plantas.proyectos.show', [$planta->id, $proyecto->id]);
    }

    public function update(UpdateProyectoRequest $request, Planta $planta, Proyecto $proyecto, ProyectosAction $action): RedirectResponse
    {
        $action->update($proyecto, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyecto actualizado.']);

        return back();
    }

    public function destroy(Planta $planta, Proyecto $proyecto, ProyectosAction $action): RedirectResponse
    {
        $action->delete($proyecto);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyecto eliminado.']);

        return redirect()->route('ingenierias.plantas.show', $planta->id);
    }
}
