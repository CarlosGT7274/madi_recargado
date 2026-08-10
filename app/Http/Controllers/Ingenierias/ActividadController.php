// app/Http/Controllers/Ingenierias/ActividadController.php
<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Actividades\ActividadesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Actividades\StoreActividadRequest;
use App\Http\Requests\Ingenierias\Actividades\UpdateActividadRequest;
use App\Models\Partida;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;

class ActividadController extends Controller
{
    public function store(StoreActividadRequest $request, Planta $planta, Proyecto $proyecto, ActividadesAction $action): RedirectResponse
    {
        $action->create($proyecto, $request->validated());

        return back();
    }

    public function update(UpdateActividadRequest $request, Planta $planta, Proyecto $proyecto, Partida $actividad, ActividadesAction $action): RedirectResponse
    {
        $action->update($actividad, $request->validated());

        return back();
    }

    public function destroy(Planta $planta, Proyecto $proyecto, Partida $actividad, ActividadesAction $action): RedirectResponse
    {
        $action->delete($actividad);

        return back();
    }
}
