<?php

namespace App\Actions\Ingenierias\Planeacion;

use App\Actions\Notificaciones\NotificacionesAction;
use App\Models\CompromisoEntrega;
use App\Models\Permiso;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Support\Accion;
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
        $plantas = Planta::orderBy('nombre')->get(['id', 'nombre']);

        $proyectos = Proyecto::orderBy('nombre')->get(['id', 'nombre', 'planta_id']);

        $residentes = Planeacion::with('usuario:id,name')
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
        $planeacion->usuario?->loadMissing('compromisos');

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
            'puedeEditar' => $planeacion->usuario_id === Auth::id()
                && in_array($planeacion->estado, ['borrador', 'rechazada'], true),
            'compromiso' => $this->compromisoInfoPara($planeacion),
        ];
    }

    /**
     * Compromiso vigente (resuelto por especificidad) para el residente de
     * esta Planeación, si tiene alguno.
     */
    private function compromisoInfoPara(Planeacion $planeacion): ?array
    {
        $usuario = $planeacion->usuario;
        $compromiso = $usuario?->compromisoVigentePara($planeacion);

        if ($compromiso === null) {
            return null;
        }

        return [
            'id' => $compromiso->id,
            'fecha' => $compromiso->fecha->format('d/m/Y H:i'),
            'vencido' => $compromiso->vencido(),
            'nota' => $compromiso->nota,
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

        $compromiso = $planeacion->usuario->compromisoVigentePara($planeacion);

        if ($compromiso !== null && $compromiso->vencido()) {
            throw ValidationException::withMessages([
                'compromiso' => "Tu fecha compromiso de entrega venció el {$compromiso->fecha->format('d/m/Y H:i')}. Contacta a tu supervisor.",
            ]);
        }

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

    private function supervisoresDe(Planeacion $planeacion): Collection
    {
        return $this->usuariosConOperacion('supervisar')
            ->filter(fn (User $u) => $u->plantasAsignadas()->pluck('plantas.id')->contains($planeacion->planta_id))
            ->values();
    }

    private function usuariosConOperacion(string $operacion): Collection
    {
        $permiso = Permiso::whereHas('padre', fn ($q) => $q->where('endpoint', 'ingenierias'))
            ->where('endpoint', 'planeacion')
            ->first();

        if ($permiso === null) {
            return collect();
        }

        return Role::with('usuarios')
            ->get()
            ->filter(fn (Role $rol) => $rol->tieneOperacion($permiso, $operacion))
            ->flatMap(fn (Role $rol) => $rol->usuarios)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    public function actualizarCronograma(Planeacion $planeacion, array $data): Planeacion
    {
        abort_unless(in_array($planeacion->estado, ['borrador', 'rechazada'], true), 422, 'Solo una planeación en borrador o rechazada puede corregirse.');
        abort_unless($planeacion->usuario_id === Auth::id(), 403, 'Solo el residente que la creó puede corregirla.');

        $planeacion->asignaciones()->delete();

        foreach ($data['asignaciones'] ?? [] as $asignacion) {
            $planeacion->asignaciones()->create([
                'partida_id' => $asignacion['partida_id'],
                'empleado_id' => $asignacion['empleado_id'],
                'dia_semana' => $asignacion['dia_semana'],
                'horas_trabajadas' => $asignacion['horas_trabajadas'],
                'estado' => 'asignado',
            ]);
        }

        if ($planeacion->estado === 'rechazada') {
            $planeacion->update([
                'estado' => 'borrador',
                'comentarios_aprobacion' => $data['nota'] ?? null,
                'fecha_rechazo' => null,
            ]);
        } elseif (array_key_exists('nota', $data) && $data['nota'] !== null) {
            $planeacion->update(['comentarios_aprobacion' => $data['nota']]);
        }

        return $planeacion->fresh();
    }

    // =====================================================================
    // Compromisos de entrega (compromisos_entrega): varios por usuario,
    // cada uno opcionalmente ligado a planta/proyecto.
    // =====================================================================

    /**
     * Lista para la columna "maestro" del modal del supervisor: un
     * resumen por usuario (no el detalle de cada compromiso, eso lo trae
     * compromisosDeUsuario() bajo demanda cuando se selecciona uno).
     */
    public function usuariosConCompromiso(): Collection
    {
        $usuarios = $this->usuariosConOperacion(Accion::CREATE);

        $usuarios->each->load('compromisos');

        return $usuarios->map(function (User $u) {
            $ordenados = $u->compromisos->sortBy('fecha');
            $vigentes = $ordenados->reject(fn (CompromisoEntrega $c) => $c->vencido());
            $proximo = $vigentes->first() ?? $ordenados->sortByDesc('fecha')->first();

            return [
                'id' => $u->id,
                'nombre' => $u->name,
                'totalCompromisos' => $u->compromisos->count(),
                'vencidos' => $u->compromisos
                    ->filter(fn (CompromisoEntrega $c) => $c->vencido())
                    ->count(),
                'proximaFecha' => $proximo?->fecha->toIso8601String(),
            ];
        });
    }

    /** Detalle completo de compromisos de un usuario, para el panel derecho del modal. */
    public function compromisosDeUsuario(User $usuario): Collection
    {
        return $usuario->compromisos()
            ->with('planta:id,nombre', 'proyecto:id,nombre,folio')
            ->orderBy('fecha')
            ->get()
            ->map(fn (CompromisoEntrega $c) => $this->compromisoResumen($c));
    }

    private function compromisoResumen(CompromisoEntrega $c): array
    {
        return [
            'id' => $c->id,
            'fecha' => $c->fecha->toIso8601String(),
            'nota' => $c->nota,
            'vencido' => $c->vencido(),
            'planta' => $c->planta ? ['id' => $c->planta->id, 'nombre' => $c->planta->nombre] : null,
            'proyecto' => $c->proyecto ? ['id' => $c->proyecto->id, 'nombre' => $c->proyecto->nombre, 'folio' => $c->proyecto->folio] : null,
        ];
    }

    public function crearCompromiso(User $usuario, array $data): CompromisoEntrega
    {
        $compromiso = $usuario->compromisos()->create($data);

        $this->notificarCompromiso($usuario, $compromiso, esNuevo: true);

        return $compromiso;
    }

    public function actualizarCompromisoExistente(CompromisoEntrega $compromiso, array $data): CompromisoEntrega
    {
        $compromiso->update($data);
        $compromiso->refresh();

        $this->notificarCompromiso($compromiso->usuario, $compromiso, esNuevo: false);

        return $compromiso;
    }

    public function eliminarCompromiso(CompromisoEntrega $compromiso): void
    {
        $usuario = $compromiso->usuario;
        $alcance = $this->alcanceLabel($compromiso);

        $compromiso->delete();

        $this->notificaciones->crearParaUsuario($usuario, [
            'mensaje' => "Tu supervisor quitó un compromiso de entrega ({$alcance}).",
            'destino_area' => 'planeacion',
            'modulo' => 'ingenierias.planeacion',
            'tipo_entidad' => 'compromiso_entrega',
            'entidad_id' => $usuario->id,
        ]);
    }

    private function notificarCompromiso(User $usuario, CompromisoEntrega $compromiso, bool $esNuevo): void
    {
        $fechaLabel = $compromiso->fecha->format('d/m/Y H:i');
        $alcance = $this->alcanceLabel($compromiso);
        $accion = $esNuevo ? 'Nuevo compromiso de entrega' : 'Se actualizó tu compromiso de entrega';

        $mensaje = "{$accion} ({$alcance}): {$fechaLabel}.";

        if (! empty($compromiso->nota)) {
            $mensaje .= " Nota de tu supervisor: {$compromiso->nota}";
        }

        $this->notificaciones->crearParaUsuario($usuario, [
            'mensaje' => $mensaje,
            'destino_area' => 'planeacion',
            'modulo' => 'ingenierias.planeacion',
            'tipo_entidad' => 'compromiso_entrega',
            'entidad_id' => $compromiso->id,
        ]);
    }

    private function alcanceLabel(CompromisoEntrega $compromiso): string
    {
        $compromiso->loadMissing('planta', 'proyecto');

        if ($compromiso->proyecto) {
            return $compromiso->proyecto->nombre;
        }
        if ($compromiso->planta) {
            return $compromiso->planta->nombre;
        }

        return 'general';
    }
}
