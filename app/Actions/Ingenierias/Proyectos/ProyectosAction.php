<?php

namespace App\Actions\Ingenierias\Proyectos;

use App\Models\Planta;
use App\Models\Proyecto;
use App\Services\FolioService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProyectosAction
{
    public function __construct(
        private readonly FolioService $folios,
    ) {}

    public function list(Planta $planta): Collection
    {
        return $planta->proyectos()
            ->latest('id')
            ->get()
            ->map(fn (Proyecto $p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'nombre' => $p->nombre,
                'tipo' => $p->tipo,
                'estado' => $p->estado,
                'bloqueado' => $p->bloqueado,
                'creado' => $p->fecha_creacion
                    ? Carbon::parse($p->fecha_creacion)->format('d/m/Y')
                    : null,
                'creado_iso' => $p->fecha_creacion
                    ? Carbon::parse($p->fecha_creacion)->format('Y-m-d')
                    : null,
            ]);
    }

    public function detail(Proyecto $proyecto): array
    {
        return [
            'id' => $proyecto->id,
            'planta_id' => $proyecto->planta_id,
            'folio' => $proyecto->folio,
            'tipo' => $proyecto->tipo,
            'nombre' => $proyecto->nombre,
            'descripcion' => $proyecto->descripcion,
            'estado' => $proyecto->estado,
            'completado' => $proyecto->estaCompletado(),
            'creado' => $proyecto->fecha_creacion
                ? Carbon::parse($proyecto->fecha_creacion)->format('d/m/Y H:i')
                : null,
        ];
    }

    public function create(Planta $planta, array $data): Proyecto
    {
        $data['folio'] = $this->folios->siguiente('ingenierias', 'proyecto', 'PRY');

        return $planta->proyectos()->create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function update(Proyecto $proyecto, array $data): Proyecto
    {
        $proyecto->update($data);

        return $proyecto;
    }

    public function delete(Proyecto $proyecto): void
    {
        $proyecto->delete();
    }
}
