<?php

namespace App\Actions\Ingenierias\Planeacion;

use App\Models\Partida;
use App\Models\Planeacion;
use App\Models\PlaneacionAsignacion;
use Illuminate\Support\Collection;

class PlaneacionAsignacionesAction
{
    public function __construct(
        private readonly PlaneacionIncidenciasAction $incidencias,
    ) {}

    /**
     * Asignaciones de la planeación, agrupadas por partida — es como se
     * consume en la vista (una partida, varios empleados/días debajo).
     */
    public function listAgrupadoPorPartida(Planeacion $planeacion): Collection
    {
        $asignaciones = $planeacion->asignaciones()
            ->with('partida', 'empleado', 'incidencias')
            ->get();

        return $asignaciones
            ->groupBy('partida_id')
            ->map(function (Collection $grupo) {
                $partida = $grupo->first()->partida;

                return [
                    'partida' => [
                        'id' => $partida->id,
                        'descripcion' => $partida->descripcion,
                    ],
                    'asignaciones' => $grupo->map(fn (PlaneacionAsignacion $a) => $this->resumen($a))->values(),
                ];
            })
            ->values();
    }

    private function resumen(PlaneacionAsignacion $a): array
    {
        return [
            'id' => $a->id,
            'empleado' => ['id' => $a->empleado->id, 'nombre' => $a->empleado->nombre],
            'diaSemana' => $a->dia_semana,
            'estado' => $a->estado,
            'horasTrabajadas' => (float) $a->horas_trabajadas,
            'horasExtra' => (float) $a->horas_extra,
            'incidencias' => $a->incidencias->map(fn ($i) => [
                'id' => $i->id,
                'tipo' => $i->tipo,
                'diaAnterior' => $i->dia_anterior,
                'diaNuevo' => $i->dia_nuevo,
                'horasExtra' => $i->horas_extra !== null ? (float) $i->horas_extra : null,
                'fecha' => $i->fecha?->format('d/m/Y'),
                'notas' => $i->notas,
                'creada' => $i->fecha_creacion?->format('d/m/Y H:i'),
            ]),
        ];
    }

    /** Partidas del proyecto disponibles para asignar (mismas que Actividades). */
    public function partidasDisponibles(Planeacion $planeacion): Collection
    {
        return Partida::where('proyecto_id', $planeacion->proyecto_id)
            ->whereNotNull('unidad')
            ->orWhere(function ($q) use ($planeacion) {
                $q->where('proyecto_id', $planeacion->proyecto_id)->whereNull('partida_id');
            })
            ->get()
            ->map(fn (Partida $p) => ['id' => $p->id, 'descripcion' => $p->descripcion]);
    }

    public function create(Planeacion $planeacion, array $data): PlaneacionAsignacion
    {
        return $planeacion->asignaciones()->create([
            ...$data,
            'estado' => $data['estado'] ?? 'asignado',
        ]);
    }

    /**
     * Si cambia día u horas extra respecto al valor anterior, registra la
     * incidencia correspondiente automáticamente — el residente no
     * necesita un paso separado para que quede en el historial.
     */
    public function update(PlaneacionAsignacion $asignacion, array $data, ?string $motivo = null): PlaneacionAsignacion
    {
        $diaAnterior = $asignacion->dia_semana;
        $horasExtraAnterior = (float) $asignacion->horas_extra;

        $asignacion->update($data);

        if (array_key_exists('dia_semana', $data) && $data['dia_semana'] !== $diaAnterior) {
            $this->incidencias->registrar($asignacion, [
                'tipo' => 'cambio_dia',
                'dia_anterior' => $diaAnterior,
                'dia_nuevo' => $asignacion->dia_semana,
                'notas' => $motivo,
            ]);
        }

        if (array_key_exists('horas_extra', $data) && (float) $data['horas_extra'] !== $horasExtraAnterior && (float) $asignacion->horas_extra > 0) {
            $this->incidencias->registrar($asignacion, [
                'tipo' => 'horas_extra',
                'horas_extra' => $asignacion->horas_extra,
                'notas' => $motivo,
            ]);
        }

        return $asignacion->fresh();
    }

    public function delete(PlaneacionAsignacion $asignacion): void
    {
        $asignacion->delete();
    }
}
