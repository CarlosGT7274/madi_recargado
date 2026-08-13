<?php

namespace App\Actions\Ingenierias\Planeacion;

use App\Actions\Notificaciones\NotificacionesAction;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PlaneacionesAction
{
    public function __construct(
        private readonly NotificacionesAction $notificaciones,
    ) {}

    /**
     * Controla si el usuario ve la perspectiva GLOBAL (todas las
     * planeaciones de sus plantas asignadas) o la RESTRINGIDA (solo las
     * suyas). Independiente de `puedeAprobar()` — un usuario puede tener
     * `supervisar` sin `aprobar`, y viceversa.
     */
    public function puedeSupervisar(User $usuario): bool
    {
        return $usuario->puedePorEndpoint('ingenierias.planeacion', 'supervisar');
    }

    /**
     * Controla ÚNICAMENTE el botón de aprobar/rechazar. No decide qué
     * vista recibe el usuario — eso es responsabilidad de
     * `puedeSupervisar()`.
     */
    public function puedeAprobar(User $usuario): bool
    {
        return $usuario->puedePorEndpoint('ingenierias.planeacion', 'aprobar');
    }

    public function listPropias(User $usuario): Collection
    {
        $query = Planeacion::where('usuario_id', $usuario->id)
            ->with('proyecto', 'planta', 'usuario', 'aprobador');

        return $query->latest('anio')->latest('semana')->get()->map(fn (Planeacion $p) => $this->resumen($p));
    }

    public function listPorProyecto(Proyecto $proyecto, User $usuario): Collection
    {
        $query = $proyecto->planeaciones()->with('usuario', 'aprobador');

        if (! $this->puedeSupervisar($usuario)) {
            $query->where('usuario_id', $usuario->id);
        }

        return $query->latest('anio')->latest('semana')->get()->map(fn (Planeacion $p) => $this->resumen($p));
    }

    public function listProgramacion(User $usuario, ?Carbon $desde, ?Carbon $hasta): Collection
    {
        $plantaIds = $usuario->plantasAsignadas()->pluck('plantas.id');

        $query = Planeacion::whereIn('planta_id', $plantaIds)
            ->with('proyecto', 'planta', 'usuario');

        if ($desde && $hasta) {
            $query->where(function ($q) use ($desde, $hasta) {
                // Approximate filtering based on anio and semana for the date range
                $q->whereBetween('anio', [$desde->year, $hasta->year]);
            });
        }

        return $query->latest('anio')->latest('semana')
            ->get()
            ->map(fn (Planeacion $p) => $this->resumen($p));
    }

    private function resumen(Planeacion $p): array
    {
        return [
            'id' => $p->id,
            'semana' => $p->semana,
            'anio' => $p->anio,
            'estado' => $p->estado,
            'reportadaNomina' => $p->reportada_nomina,
            'proyecto' => $p->relationLoaded('proyecto') && $p->proyecto ? [
                'id' => $p->proyecto->id,
                'nombre' => $p->proyecto->nombre,
                'folio' => $p->proyecto->folio,
            ] : null,
            'planta' => $p->relationLoaded('planta') && $p->planta ? [
                'id' => $p->planta->id,
                'nombre' => $p->planta->nombre,
            ] : null,
            'residente' => $p->usuario?->name,
            'residenteId' => $p->usuario_id,
            'aprobador' => $p->aprobador?->name,
            'fechaInicio' => $p->fechaInicio()->format('Y-m-d'),
            'fechaFin' => $p->fechaFin()->format('Y-m-d'),
            'fechaEnvio' => $p->fecha_envio?->format('d/m/Y H:i'),
            'fechaAprobacion' => $p->fecha_aprobacion?->format('d/m/Y H:i'),
            'comentariosAprobacion' => $p->comentarios_aprobacion,
            // Solo vienen poblados cuando el query los agregó explícitamente
            // (withSum/withCount/eager-load de asignaciones en
            // listVistaGeneral) — los demás listados no pagan ese costo si
            // no lo necesitan; acceder a un atributo/relación no cargada
            // aquí simplemente cae en null/colección vacía.
            'horasProgramadas' => $p->horas_programadas !== null ? (float) $p->horas_programadas : 0.0,
            'incidenciasCount' => $p->incidencias_count ?? 0,
            'empleados' => $p->relationLoaded('asignaciones')
                ? $p->asignaciones->pluck('empleado')->filter()->unique('id')
                    ->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre])->values()->all()
                : [],
            'partidas' => $p->relationLoaded('asignaciones')
                ? $p->asignaciones->pluck('partida')->filter()->unique('id')
                    ->map(fn ($pa) => ['id' => $pa->id, 'descripcion' => $pa->descripcion])->values()->all()
                : [],
        ];
    }

    /**
     * Fuente ÚNICA de datos para la vista general de Planeación. El scope
     * (propias vs. las de tus plantas asignadas) depende de
     * `puedeSupervisar()`. Sirve tanto a MisPlaneaciones.vue como al
     * overview anual de Planificador.vue — este último necesita las horas
     * programadas, incidencias, empleados y partidas por planeación para
     * construir el calendario y el drill-down sin pedir nada más al
     * servidor, así que se cargan siempre aquí (el costo es acotado al
     * scope de plantas asignadas, igual que el resto de los campos).
     */
    public function listVistaGeneral(User $usuario): Collection
    {
        $query = $this->puedeSupervisar($usuario)
            ? Planeacion::whereIn('planta_id', $usuario->plantasAsignadas()->pluck('plantas.id'))
            : Planeacion::where('usuario_id', $usuario->id);

        return $query
            ->with([
                'proyecto',
                'planta',
                'usuario',
                'aprobador',
                'asignaciones.empleado:id,nombre',
                'asignaciones.partida:id,descripcion',
            ])
            ->withSum('asignaciones as horas_programadas', 'horas_trabajadas')
            ->withCount('incidencias')
            ->latest('anio')->latest('semana')
            ->get()
            ->map(fn (Planeacion $p) => $this->resumen($p));
    }

    /**
     * Opciones para los filtros de la vista de Supervisor (planta,
     * proyecto, residente). Mismo scope de plantas asignadas que
     * `listVistaGeneral()` — un filtro nunca debe ofrecer algo que el
     * usuario no podría ver de todos modos.
     *
     * @return array{plantas: Collection, proyectos: Collection, residentes: Collection}
     */
    public function filtrosDisponibles(User $usuario): array
    {
        $plantaIds = $usuario->plantasAsignadas()->pluck('plantas.id');

        $plantas = Planta::whereIn('id', $plantaIds)->orderBy('nombre')->get(['id', 'nombre']);

        $proyectos = Proyecto::whereIn('planta_id', $plantaIds)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'planta_id']);

        $residentes = Planeacion::whereIn('planta_id', $plantaIds)
            ->with('usuario:id,name')
            ->get()
            ->pluck('usuario')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->map(fn (User $u) => ['id' => $u->id, 'nombre' => $u->name])
            ->values();

        return [
            'plantas' => $plantas,
            'proyectos' => $proyectos,
            'residentes' => $residentes,
        ];
    }

    public function detail(Planeacion $planeacion): array
    {
        $planeacion->loadMissing('planta', 'proyecto', 'usuario', 'aprobador');

        return [
            'id' => $planeacion->id,
            'semana' => $planeacion->semana,
            'anio' => $planeacion->anio,
            'estado' => $planeacion->estado,
            'reportadaNomina' => $planeacion->reportada_nomina,
            'fechaReporteNomina' => $planeacion->fecha_reporte_nomina?->format('d/m/Y H:i'),
            'fechaEnvio' => $planeacion->fecha_envio?->format('d/m/Y H:i'),
            'fechaAprobacion' => $planeacion->fecha_aprobacion?->format('d/m/Y H:i'),
            'fechaRechazo' => $planeacion->fecha_rechazo?->format('d/m/Y H:i'),
            'comentariosAprobacion' => $planeacion->comentarios_aprobacion,
            'planta' => ['id' => $planeacion->planta->id, 'nombre' => $planeacion->planta->nombre],
            'proyecto' => [
                'id' => $planeacion->proyecto->id,
                'nombre' => $planeacion->proyecto->nombre,
                'folio' => $planeacion->proyecto->folio,
            ],
            'residente' => [
                'id' => $planeacion->usuario?->id,
                'nombre' => $planeacion->usuario?->name,
                'firmaUrl' => $planeacion->usuario?->firma_url,
            ],
            'aprobador' => $planeacion->aprobador?->name,
        ];
    }

    public function create(Proyecto $proyecto, array $data): Planeacion
    {
        $existente = $proyecto->planeaciones()
            ->where('semana', $data['semana'])
            ->where('anio', $data['anio'])
            ->first();

        if ($existente !== null) {
            throw ValidationException::withMessages([
                'semana' => 'Ya existe una planeación para esta semana en este proyecto.',
            ]);
        }

        return $proyecto->planeaciones()->create([
            ...$data,
            'planta_id' => $proyecto->planta_id,
            'usuario_id' => Auth::id(),
            'estado' => 'borrador',
        ]);
    }

    public function enviar(Planeacion $planeacion): Planeacion
    {
        abort_unless($planeacion->estado === 'borrador', 422, 'Solo una planeación en borrador puede enviarse.');

        $planeacion->update(['estado' => 'enviada', 'fecha_envio' => now()]);

        $this->notificarIngenieros($planeacion, "La planeación de {$planeacion->proyecto->nombre} (semana {$planeacion->semana}/{$planeacion->anio}) fue enviada para revisión.");

        return $planeacion->fresh();
    }

    public function aprobar(Planeacion $planeacion, ?string $comentarios = null): Planeacion
    {
        abort_unless($planeacion->estado === 'enviada', 422, 'Solo una planeación enviada puede aprobarse.');

        $planeacion->update([
            'estado' => 'aprobada',
            'fecha_aprobacion' => now(),
            'aprobador_id' => Auth::id(),
            'comentarios_aprobacion' => $comentarios,
        ]);

        $this->notificaciones->crearParaUsuario($planeacion->usuario, [
            'mensaje' => "Tu planeación de la semana {$planeacion->semana}/{$planeacion->anio} fue aprobada.",
            'destino_area' => 'planeacion',
            'modulo' => 'ingenierias.planeacion',
            'tipo_entidad' => 'planeacion',
            'entidad_id' => $planeacion->id,
        ]);

        return $planeacion->fresh();
    }

    public function rechazar(Planeacion $planeacion, string $comentarios): Planeacion
    {
        abort_unless($planeacion->estado === 'enviada', 422, 'Solo una planeación enviada puede rechazarse.');

        $planeacion->update([
            'estado' => 'rechazada',
            'fecha_rechazo' => now(),
            'aprobador_id' => Auth::id(),
            'comentarios_aprobacion' => $comentarios,
        ]);

        $this->notificaciones->crearParaUsuario($planeacion->usuario, [
            'mensaje' => "Tu planeación de la semana {$planeacion->semana}/{$planeacion->anio} fue rechazada: {$comentarios}",
            'destino_area' => 'planeacion',
            'modulo' => 'ingenierias.planeacion',
            'tipo_entidad' => 'planeacion',
            'entidad_id' => $planeacion->id,
        ]);

        return $planeacion->fresh();
    }

    public function reportarNomina(Planeacion $planeacion): Planeacion
    {
        abort_unless($planeacion->estado === 'aprobada', 422, 'Solo una planeación aprobada puede reportarse a nómina.');
        abort_if($planeacion->reportada_nomina, 422, 'Esta planeación ya fue reportada a nómina.');

        $planeacion->update([
            'reportada_nomina' => true,
            'fecha_reporte_nomina' => now(),
        ]);

        return $planeacion->fresh();
    }

    public function delete(Planeacion $planeacion): void
    {
        abort_unless($planeacion->estado === 'borrador', 422, 'Solo una planeación en borrador puede eliminarse.');

        $planeacion->delete();
    }

    private function notificarIngenieros(Planeacion $planeacion, string $mensaje): void
    {
        $planeacion->loadMissing('planta.ingenieros');

        $this->notificaciones->crearParaUsuarios($planeacion->planta->ingenieros, [
            'mensaje' => $mensaje,
            'destino_area' => 'planeacion',
            'modulo' => 'ingenierias.planeacion',
            'tipo_entidad' => 'planeacion',
            'entidad_id' => $planeacion->id,
        ]);
    }
}
