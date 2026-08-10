<?php

namespace App\Http\Controllers\Ingenierias\Planeacion;

use App\Actions\Ingenierias\Planeacion\PlaneacionIncidenciasAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Planeacion\StoreIncidenciaRequest;
use App\Models\Planeacion;
use App\Models\PlaneacionAsignacion;
use Illuminate\Http\RedirectResponse;

class IncidenciaController extends Controller
{
    public function store(StoreIncidenciaRequest $request, Planeacion $planeacion, PlaneacionAsignacion $asignacion, PlaneacionIncidenciasAction $action): RedirectResponse
    {
        $action->registrar($asignacion, $request->validated());

        return back();
    }
}
