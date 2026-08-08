<?php

namespace App\Actions\Ingenierias\Plantas;

use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Planta;
use App\Services\FolioService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PlantasAction
{
    public function __construct(
        private readonly FolioService $folios,
    ) {}

    /** Listar todas las plantas con estadísticas agregadas de sus levantamientos y cotizaciones. */
    public function list(): Collection
    {
        $plantas = Planta::query()->withCount('proyectos')->latest('id')->get();

        $levantamientosPorPlanta = Levantamiento::query()
            ->select('planta_id', 'estatus_admin', 'prioridad', 'fecha_levantamiento_programada')
            ->get()
            ->groupBy('planta_id');

        $cotizadosPorPlanta = Cotizacion::query()
            ->join('proyectos', 'proyectos.id', '=', 'cotizaciones.proyecto_id')
            ->selectRaw('proyectos.planta_id, count(*) as total')
            ->groupBy('proyectos.planta_id')
            ->pluck('total', 'planta_id');

        return $plantas->map(function (Planta $planta) use ($levantamientosPorPlanta, $cotizadosPorPlanta) {
            $levantamientos = $levantamientosPorPlanta->get($planta->id, collect());

            $porEstatus = $levantamientos->countBy('estatus_admin')
                ->sortDesc()
                ->map(fn (int $total, string $estatus) => ['estatus' => $estatus, 'total' => $total])
                ->values();

            return [
                'id' => $planta->id,
                'folio' => $planta->folio,
                'nombre' => $planta->nombre,
                'direccion' => $planta->direccion,
                'activa' => $planta->activa,
                'proyectosCount' => $planta->proyectos_count,
                'levantamientosCount' => $levantamientos->count(),
                'porEstatus' => $porEstatus,
                'urgentes' => $levantamientos->where('prioridad', 'urgente')->count(),
                'programados' => $levantamientos->whereNotNull('fecha_levantamiento_programada')->count(),
                'cotizados' => $cotizadosPorPlanta->get($planta->id, 0),
                'creada' => $planta->fecha_creacion
                    ? Carbon::parse($planta->fecha_creacion)->format('d/m/Y')
                    : null,
            ];
        })->values();
    }

    /** Obtener detalle de una planta */
    public function detail(Planta $planta): array
    {
        return [
            'id' => $planta->id,
            'folio' => $planta->folio,
            'nombre' => $planta->nombre,
            'direccion' => $planta->direccion,
            'descripcion' => $planta->descripcion,
            'activa' => $planta->activa,
            'creada' => $planta->fecha_creacion
                ? Carbon::parse($planta->fecha_creacion)->format('d/m/Y H:i')
                : null,
            'modificada' => $planta->fecha_modificacion
                ? Carbon::parse($planta->fecha_modificacion)->format('d/m/Y H:i')
                : null,
        ];
    }

    /** Crear una nueva planta */
    public function create(array $data): Planta
    {
        $data['folio'] = $this->folios->siguiente('ingenierias', 'planta', 'PLT');

        return Planta::create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    /** Actualizar una planta existente */
    public function update(Planta $planta, array $data): Planta
    {
        $planta->update($data);

        return $planta;
    }

    /** Eliminar una planta */
    public function delete(Planta $planta): void
    {
        $planta->delete();
    }
}
