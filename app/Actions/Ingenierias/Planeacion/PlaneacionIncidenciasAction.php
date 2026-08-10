<?php

namespace App\Actions\Ingenierias\Planeacion;

use App\Models\PlaneacionAsignacion;
use App\Models\PlaneacionIncidencia;
use Illuminate\Support\Facades\Auth;

class PlaneacionIncidenciasAction
{
    public function registrar(PlaneacionAsignacion $asignacion, array $data): PlaneacionIncidencia
    {
        return $asignacion->incidencias()->create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function delete(PlaneacionIncidencia $incidencia): void
    {
        $incidencia->delete();
    }
}
