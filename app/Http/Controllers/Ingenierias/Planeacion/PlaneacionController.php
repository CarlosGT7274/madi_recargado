<?php

namespace App\Http\Controllers\Ingenierias\Planeacion;

use App\Actions\Ingenierias\Actividades\ActividadesAction;
use App\Actions\Ingenierias\Planeacion\PlaneacionAsignacionesAction;
use App\Actions\Ingenierias\Planeacion\PlaneacionesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Planeacion\RechazarPlaneacionRequest;
use App\Http\Requests\Ingenierias\Planeacion\StorePlaneacionRequest;
use App\Models\Empleado;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Support\Accion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaneacionController extends Controller
{
    /**
     * La perspectiva (qué componente Vue recibe el usuario) la decide
     * exclusivamente `puedeSupervisar()`. `puedeAprobar()` es una
     * operación aparte que solo se usa para mostrar/ocultar el botón de
     * aprobar-rechazar dentro de la vista de Planificador — nunca para
     * elegir qué vista se renderiza.
     */
    public function index(Request $request, PlaneacionesAction $action): Response
    {
        $usuario = $request->user();

        if ($action->puedeSupervisar($usuario)) {
            return Inertia::render('ingenierias/planeacion/Planificador', [
                'puedeAprobar' => $action->puedeAprobar($usuario),
                'filtros' => Inertia::defer(fn () => $action->filtrosDisponibles($usuario)),
                'planeaciones' => Inertia::defer(fn () => $action->listVistaGeneral($usuario)),
            ]);
        }

        return Inertia::render('ingenierias/planeacion/MisPlaneaciones', [
            'puedeCrear' => $usuario->puedePorEndpoint('ingenierias.planeacion', Accion::CREATE),
            'puedeEliminar' => $usuario->puedePorEndpoint('ingenierias.planeacion', Accion::DELETE),
            'planeaciones' => Inertia::defer(fn () => $action->listVistaGeneral($usuario)),
        ]);
    }

    public function create(Request $request, PlaneacionesAction $action): Response
    {
        $usuario = $request->user();
        $puedeSupervisar = $action->puedeSupervisar($usuario);

        $plantas = $puedeSupervisar
            ? $usuario->plantasAsignadas()
                ->with('proyectos:id,planta_id,nombre,folio,tipo')
                ->get(['plantas.id', 'plantas.nombre', 'plantas.folio'])
                ->map(fn (Planta $p) => $this->plantaOpcion($p))
                ->values()
            : $usuario->proyectosAsignados()
                ->with('planta:id,nombre,folio')
                ->get()
                ->groupBy('planta_id')
                ->map(function ($proyectos) {
                    $planta = $proyectos->first()->planta;

                    return [
                        'id' => $planta->id,
                        'nombre' => $planta->nombre,
                        'folio' => $planta->folio,
                        'proyectos' => $proyectos->map(fn (Proyecto $p) => $this->proyectoOpcion($p))->values(),
                    ];
                })
                ->values();

        return Inertia::render('ingenierias/planeacion/Create', [
            'plantas' => $plantas,
            'empleados' => Empleado::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'puesto']),
        ]);
    }

    private function plantaOpcion(Planta $p): array
    {
        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'folio' => $p->folio,
            'proyectos' => $p->proyectos->map(fn (Proyecto $pr) => $this->proyectoOpcion($pr))->values(),
        ];
    }

    private function proyectoOpcion(Proyecto $p): array
    {
        return ['id' => $p->id, 'nombre' => $p->nombre, 'folio' => $p->folio, 'tipo' => $p->tipo];
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

        return redirect()->route('ingenierias.planeacion.show', $planeacion->id);
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

        return redirect()->route('ingenierias.planeacion.index');
    }
}
