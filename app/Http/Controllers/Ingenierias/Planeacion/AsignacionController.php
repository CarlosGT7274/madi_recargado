<?php

namespace App\Http\Controllers\Ingenierias\Planeacion;

use App\Actions\Ingenierias\Planeacion\PlaneacionAsignacionesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Planeacion\StoreAsignacionRequest;
use App\Http\Requests\Ingenierias\Planeacion\UpdateAsignacionRequest;
use App\Models\Planeacion;
use App\Models\PlaneacionAsignacion;
use Illuminate\Http\RedirectResponse;

class AsignacionController extends Controller
{
    public function store(StoreAsignacionRequest $request, Planeacion $planeacion, PlaneacionAsignacionesAction $action): RedirectResponse
    {
        $action->create($planeacion, $request->validated());

        return back();
    }

    public function update(UpdateAsignacionRequest $request, Planeacion $planeacion, PlaneacionAsignacion $asignacion, PlaneacionAsignacionesAction $action): RedirectResponse
    {
        $data = $request->validated();
        $motivo = $data['motivo'] ?? null;
        unset($data['motivo']);

        $action->update($asignacion, $data, $motivo);

        return back();
    }

    public function destroy(Planeacion $planeacion, PlaneacionAsignacion $asignacion, PlaneacionAsignacionesAction $action): RedirectResponse
    {
        $action->delete($asignacion);

        return back();
    }
}
