<?php

namespace App\Http\Controllers\Ingenierias\Planeacion;

use App\Actions\Ingenierias\Actividades\ActividadesAction;
use App\Actions\Ingenierias\Planeacion\PlaneacionAsignacionesAction;
use App\Actions\Ingenierias\Planeacion\PlaneacionesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Planeacion\RechazarPlaneacionRequest;
use App\Http\Requests\Ingenierias\Planeacion\StorePlaneacionRequest;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaneacionController extends Controller
{
    public function index(Request $request, PlaneacionesAction $action): Response
    {
        $usuario = $request->user();
        $esSupervisor = $action->esSupervisor($usuario);

        return Inertia::render($esSupervisor ? 'ingenierias/planeacion/Supervisor' : 'ingenierias/planeacion/Residente', [
            'esSupervisor' => $esSupervisor,
            'planeaciones' => $esSupervisor
                ? $action->listParaSupervisor($usuario)
                : Inertia::defer(fn () => collect()),
            'plantas' => $esSupervisor
                ? $usuario->plantasAsignadas()->get(['plantas.id', 'plantas.nombre', 'plantas.folio'])
                : $usuario->proyectosAsignados()->with('planta')->get(),
        ]);
    }

    public function porProyecto(Request $request, Planta $planta, Proyecto $proyecto, PlaneacionesAction $action): Response
    {
        return Inertia::render('ingenierias/planeacion/PorProyecto', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre, 'folio' => $proyecto->folio],
            'planeaciones' => $action->listPorProyecto($proyecto, $request->user()),
        ]);
    }

    public function store(StorePlaneacionRequest $request, Planta $planta, Proyecto $proyecto, PlaneacionesAction $action): RedirectResponse
    {
        $planeacion = $action->create($proyecto, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación creada.']);

        return redirect()->route('planeacion.show', $planeacion->id);
    }

    public function show(
        Planeacion $planeacion,
        PlaneacionesAction $action,
        PlaneacionAsignacionesAction $asignacionesAction,
        ActividadesAction $actividadesAction,
    ): Response {
        return Inertia::render('ingenierias/planeacion/Show', [
            'planeacion' => $action->detail($planeacion),
            'asignaciones' => $asignacionesAction->listAgrupadoPorPartida($planeacion),
            'partidasDisponibles' => $actividadesAction->arbol($planeacion->proyecto),
        ]);
    }

    public function enviar(Planeacion $planeacion, PlaneacionesAction $action): RedirectResponse
    {
        $action->enviar($planeacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación enviada a revisión.']);

        return back();
    }

    public function aprobar(Request $request, Planeacion $planeacion, PlaneacionesAction $action): RedirectResponse
    {
        $action->aprobar($planeacion, $request->string('comentarios')->value() ?: null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación aprobada.']);

        return back();
    }

    public function rechazar(RechazarPlaneacionRequest $request, Planeacion $planeacion, PlaneacionesAction $action): RedirectResponse
    {
        $action->rechazar($planeacion, $request->validated('comentarios'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación rechazada.']);

        return back();
    }

    public function reportarNomina(Planeacion $planeacion, PlaneacionesAction $action): RedirectResponse
    {
        $action->reportarNomina($planeacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación reportada a nómina.']);

        return back();
    }

    public function destroy(Planeacion $planeacion, PlaneacionesAction $action): RedirectResponse
    {
        $action->delete($planeacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación eliminada.']);

        return redirect()->route('planeacion.index');
    }
}
