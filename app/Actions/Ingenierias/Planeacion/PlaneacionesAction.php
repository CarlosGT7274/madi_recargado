<?php

namespace App\Actions\Ingenierias\Planeacion;

use App\Actions\Notificaciones\NotificacionesAction;
use App\Models\Planeacion;
use App\Models\Proyecto;
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

    /**
     * Sin lógica propia: reutiliza el mecanismo central de permisos que ya
     * usa el resto del sistema (ver CotizacionController::show, que hace
     * lo mismo para 'ingenierias'). Si el usuario tiene el bit ALL sobre
     * el endpoint 'planeacion', es supervisor/ingeniero; si no, residente.
     */
    public function puedeAprobar(User $usuario): bool
    {
        return $usuario->puedePorEndpoint('ingenierias.planeacion', Accion::ALL);
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

        if (! $this->puedeAprobar($usuario)) {
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
            'aprobador' => $p->aprobador?->name,
            // Necesarios para pintar el rango en el calendario anual — antes
            // el frontend los usaba sin que existieran en este arreglo.
            'fechaInicio' => $p->fechaInicio()->format('Y-m-d'),
            'fechaFin' => $p->fechaFin()->format('Y-m-d'),
            'fechaEnvio' => $p->fecha_envio?->format('d/m/Y H:i'),
            'fechaAprobacion' => $p->fecha_aprobacion?->format('d/m/Y H:i'),
            'comentariosAprobacion' => $p->comentarios_aprobacion,
        ];
    }

    /**
     * Fuente ÚNICA de datos para la vista general de Planeación. El scope
     * (propias vs. las de tus plantas asignadas) depende del permiso ALL,
     * pero el resultado alimenta la MISMA pantalla — no una pantalla aparte.
     */
    public function listVistaGeneral(User $usuario): Collection
    {
        $query = $this->puedeAprobar($usuario)
            ? Planeacion::whereIn('planta_id', $usuario->plantasAsignadas()->pluck('plantas.id'))
            : Planeacion::where('usuario_id', $usuario->id);

        return $query->with('proyecto', 'planta', 'usuario', 'aprobador')
            ->latest('anio')->latest('semana')
            ->get()
            ->map(fn (Planeacion $p) => $this->resumen($p));
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
