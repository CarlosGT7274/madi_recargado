<?php

namespace App\Domain\Ingenierias\Proyectos;

use App\Models\Planta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlantaService
{
    /**
     * List all plantas.
     */
    public function listAll(): Collection
    {
        return Planta::query()
            ->latest('id')
            ->get()
            ->map(fn (Planta $planta) => [
                'id' => $planta->id,
                'folio' => $planta->folio,
                'nombre' => $planta->nombre,
                'creada' => $planta->fecha_creacion
                    ? Carbon::parse($planta->fecha_creacion)->format('d/m/Y')
                    : null,
                // placeholders for future counts
                'levantamientos_count' => 0,
                'por_estatus' => [],
                'urgentes' => 0,
                'programados' => 0,
                'cotizados' => 0,
            ]);
    }

    /**
     * Get detalle de una planta.
     */
    public function detail(Planta $planta): array
    {
        return [
            'id' => $planta->id,
            'folio' => $planta->folio,
            'nombre' => $planta->nombre,
        ];
    }
}
