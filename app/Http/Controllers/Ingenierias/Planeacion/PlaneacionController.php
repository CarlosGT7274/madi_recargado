<?php

namespace App\Http\Controllers\Ingenierias\Planeacion;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Actions\Ingenierias\Planeacion\PlaneacionAsignacionesAction;
use App\Actions\Ingenierias\Planeacion\PlaneacionesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Planeacion\RechazarPlaneacionRequest;
use App\Http\Requests\Ingenierias\Planeacion\StorePlaneacionRequest;
use App\Http\Requests\Ingenierias\Planeacion\UpdateCronogramaPlaneacionRequest;
use App\Models\Cotizacion;
use App\Models\Empleado;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Support\Accion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaneacionController extends Controller
{
    public function index(Request $request, PlaneacionesAction $action): Response
    {
        $usuario = $request->user();

        if ($action->puedeSupervisar($usuario)) {
            return Inertia::render('ingenierias/planeacion/Planificador', [
                'puedeAprobar' => $action->puedeAprobar($usuario),
                'puedeCrear' => $usuario->puedePorEndpoint('ingenierias.planeacion', Accion::CREATE),
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
            ? Planta::with('proyectos:id,planta_id,nombre,folio,tipo')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'folio'])
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

        if ($request->boolean('enviar_aprobacion')) {
            $planeacion = $action->enviar($planeacion);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación guardada y enviada a aprobación.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación guardada como borrador.']);
        }

        return redirect()->route('ingenierias.planeacion.show', $planeacion->id);
    }

    /**
     * Detalle real: planta/proyecto, semana/rango, estado, comentarios de
     * aprobación, y el cronograma completo (partida -> día -> empleados
     * con horas), agrupado por PlaneacionAsignacionesAction — misma
     * Action que ya alimentaba el cronograma en Create.vue, reutilizada
     * aquí en modo lectura para Show.vue.
     */
    public function show(
        Planeacion $planeacion,
        PlaneacionesAction $action,
        PlaneacionAsignacionesAction $asignacionesAction,
    ): Response {
        $usuario = request()->user();

        return Inertia::render('ingenierias/planeacion/Show', [
            'planeacion' => $action->detail($planeacion),
            'cronograma' => $asignacionesAction->listAgrupadoPorDia($planeacion),
            'puedeAprobar' => $action->puedeAprobar($usuario),
        ]);
    }

    /**
     * Corrige el cronograma de una Planeación rechazada (o borrador) sin
     * crear una nueva fila. Si el front manda enviar_aprobacion=true,
     * encadena PlaneacionesAction::enviar() en la misma request — mismo
     * patrón que store() ya usa al crear.
     */
    public function actualizarCronograma(UpdateCronogramaPlaneacionRequest $request, Planeacion $planeacion, PlaneacionesAction $action): RedirectResponse
    {
        $planeacion = $action->actualizarCronograma($planeacion, $request->validated());

        if ($request->boolean('enviar_aprobacion')) {
            $action->enviar($planeacion);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Planeación corregida y reenviada a aprobación.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Cronograma actualizado.']);
        }

        return redirect()->route('ingenierias.planeacion.show', $planeacion->id);
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

    public function cotizacionesAprobadas(Planta $planta, Proyecto $proyecto, CotizacionesAction $action): JsonResponse
    {
        return response()->json($action->listAprobadasProyecto($proyecto));
    }

    public function partidasDeCotizacion(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, PartidasAction $action): JsonResponse
    {
        return response()->json($action->disponibles($cotizacion));
    }
}
