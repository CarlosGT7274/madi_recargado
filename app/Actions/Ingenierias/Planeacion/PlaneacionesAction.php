<?php

namespace App\Actions\Ingenierias\Planeacion;

use App\Actions\Notificaciones\NotificacionesAction;
use App\Models\Permiso;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Models\Role;
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

    public function puedeSupervisar(User $usuario): bool
    {
        return $usuario->puedePorEndpoint('ingenierias.planeacion', 'supervisar');
    }

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
            'puedeEditar' => $p->usuario_id === Auth::id()
                && in_array($p->estado, ['borrador', 'rechazada'], true),
        ];
    }

    /**
     * Fuente única del calendario anual del Supervisor (Planificador.vue).
     * Para un supervisor, trae TODAS las planeaciones de las plantas que
     * tiene asignadas (plantasAsignadas()), sin filtrar por usuario_id —
     * es justamente lo que le permite ver lo que le llega a supervisar,
     * no solo lo propio. Para alguien sin permiso de supervisar, cae en
     * lo mismo que MisPlaneaciones.vue: solo lo suyo.
     */
    public function listVistaGeneral(User $usuario): Collection
    {
        $query = $this->puedeSupervisar($usuario) ? Planeacion::query() : Planeacion::where('usuario_id', $usuario->id);

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
            'fechaInicio' => $planeacion->fechaInicio()->format('Y-m-d'),
            'fechaFin' => $planeacion->fechaFin()->format('Y-m-d'),
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
            'puedeEnviar' => $planeacion->estado === 'borrador' && $planeacion->usuario_id === Auth::id(),
            'puedeEliminar' => $planeacion->estado === 'borrador' && $planeacion->usuario_id === Auth::id(),
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

        $asignaciones = $data['asignaciones'] ?? [];
        unset($data['asignaciones']);

        $planeacion = $proyecto->planeaciones()->create([
            ...$data,
            'planta_id' => $proyecto->planta_id,
            'usuario_id' => Auth::id(),
            'estado' => 'borrador',
        ]);

        foreach ($asignaciones as $asignacion) {
            $planeacion->asignaciones()->create([
                'partida_id' => $asignacion['partida_id'],
                'empleado_id' => $asignacion['empleado_id'],
                'dia_semana' => $asignacion['dia_semana'],
                'horas_trabajadas' => $asignacion['horas_trabajadas'],
                'estado' => 'asignado',
            ]);
        }

        return $planeacion;
    }

    public function enviar(Planeacion $planeacion): Planeacion
    {
        abort_unless($planeacion->estado === 'borrador', 422, 'Solo una planeación en borrador puede enviarse.');
        abort_unless($planeacion->usuario_id === Auth::id(), 403, 'Solo el residente que la creó puede enviarla.');

        $planeacion->update(['estado' => 'enviada', 'fecha_envio' => now()]);

        $this->notificarSupervisores(
            $planeacion,
            "La planeación de {$planeacion->proyecto->nombre} (semana {$planeacion->semana}/{$planeacion->anio}) fue enviada para revisión."
        );

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

    /**
     * Reemplaza al antiguo notificarIngenieros() (planta->ingenieros: una
     * simple pivot sin ninguna verificación de permiso). El destinatario
     * correcto de "enviada a aprobación" es quien de verdad puede
     * supervisar/aprobar esta Planeación: usuarios con la operación
     * `supervisar` en el permiso `ingenierias.planeacion` (mismo objeto
     * que valida Role::tienePermiso()/puedePorEndpoint() en el resto del
     * módulo), acotados a la planta de la Planeación vía
     * plantasAsignadas() — el mismo criterio de alcance que ya usa
     * PlaneacionesAction::listVistaGeneral()/filtrosDisponibles() para
     * decidir qué ve un supervisor.
     *
     * Reutiliza NotificacionesAction::crearParaUsuarios(), que ya hace el
     * broadcast(new NotificacionCreada(...)) por WebSocket — no se crea
     * ningún canal, evento ni tabla nueva.
     */
    private function notificarSupervisores(Planeacion $planeacion, string $mensaje): void
    {
        $supervisores = $this->supervisoresDe($planeacion);

        if ($supervisores->isEmpty()) {
            return;
        }

        $this->notificaciones->crearParaUsuarios($supervisores, [
            'mensaje' => $mensaje,
            'destino_area' => 'planeacion',
            'modulo' => 'ingenierias.planeacion',
            'tipo_entidad' => 'planeacion',
            'entidad_id' => $planeacion->id,
        ]);
    }

    /**
     * Usuarios que pueden supervisar ESTA Planeación: tienen operación
     * `supervisar` sobre el permiso `ingenierias.planeacion` (vía algún
     * rol que se la conceda) Y tienen la planta de la Planeación entre
     * sus plantasAsignadas().
     */
    private function supervisoresDe(Planeacion $planeacion): Collection
    {
        $permiso = Permiso::whereHas('padre', fn ($q) => $q->where('endpoint', 'ingenierias'))
            ->where('endpoint', 'planeacion')
            ->first();

        if ($permiso === null) {
            return collect();
        }

        return Role::with('usuarios')
            ->get()
            ->filter(fn (Role $rol) => $rol->tieneOperacion($permiso, 'supervisar'))
            ->flatMap(fn (Role $rol) => $rol->usuarios)
            ->unique('id')
            ->values();
    }
}
