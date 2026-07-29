<?php

namespace App\Actions\Ingenierias\Levantamientos;

use App\Models\Levantamiento;
use App\Models\Planta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LevantamientosAction
{
    public function list(Planta $planta): Collection
    {
        return $planta->levantamientos()
            ->latest('id')
            ->get()
            ->map(fn (Levantamiento $l) => [
                'id' => $l->id,
                'folio' => $l->folio,
                'nombre' => $l->nombre,
                'cliente' => $l->cliente,
                'prioridad' => $l->prioridad,
                'estatus_admin' => $l->estatus_admin,
                'creado' => $l->fecha_creacion
                    ? Carbon::parse($l->fecha_creacion)->format('d/m/Y')
                    : null,
            ]);
    }

    public function detail(Levantamiento $levantamiento): array
    {
        return [
            'id' => $levantamiento->id,
            'planta_id' => $levantamiento->planta_id,
            'folio' => $levantamiento->folio,
            'nombre' => $levantamiento->nombre,
            'cliente' => $levantamiento->cliente,
            'obra' => $levantamiento->obra,
            'solicitante' => $levantamiento->solicitante,
            'prioridad' => $levantamiento->prioridad,
            'estatus_admin' => $levantamiento->estatus_admin,
            'creado' => $levantamiento->fecha_creacion
                ? Carbon::parse($levantamiento->fecha_creacion)->format('d/m/Y H:i')
                : null,
            'modificado' => $levantamiento->fecha_modificacion
                ? Carbon::parse($levantamiento->fecha_modificacion)->format('d/m/Y H:i')
                : null,
        ];
    }

    public function create(Planta $planta, array $data): Levantamiento
    {
        return $planta->levantamientos()->create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function update(Levantamiento $levantamiento, array $data): Levantamiento
    {
        $levantamiento->update($data);

        return $levantamiento;
    }

    public function delete(Levantamiento $levantamiento): void
    {
        $levantamiento->delete();
    }
}
