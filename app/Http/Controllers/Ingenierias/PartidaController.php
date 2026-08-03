<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Partidas\PartidasAction;
use App\Exports\PartidaPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Partidas\ImportPartidasRequest;
use App\Http\Requests\Ingenierias\Partidas\StorePartidaRequest;
use App\Http\Requests\Ingenierias\Partidas\UpdatePartidaRequest;
use App\Imports\PartidasImport;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Partida;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartidaController extends Controller
{
    public function store(
        StorePartidaRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        PartidasAction $action,
    ): RedirectResponse {
        $action->create($cotizacion, $request->validated());

        return back();
    }

    public function update(
        UpdatePartidaRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        Partida $partida,
        PartidasAction $action,
    ): RedirectResponse {
        $action->update($partida, $request->validated());

        return back();
    }

    public function destroy(
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        Partida $partida,
        PartidasAction $action,
    ): RedirectResponse {
        $action->delete($partida);

        return back();
    }

    public function plantilla(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, PartidasAction $action): BinaryFileResponse
    {
        return Excel::download(
            new PartidaPlantillaExport($cotizacion, $action->arbol($cotizacion)),
            "plantilla-partidas-{$cotizacion->folio}.xlsx",
        );
    }

    public function plantillaGenerica(): BinaryFileResponse
    {
        return Excel::download(new PartidaPlantillaExport, 'plantilla-partidas.xlsx');
    }

    public function importar(
        ImportPartidasRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        PartidasAction $action,
    ): RedirectResponse {
        $import = new PartidasImport($cotizacion, $action);
        Excel::import($import, $request->file('archivo'));

        Inertia::flash('toast', [
            'type' => empty($import->errores()) ? 'success' : 'warning',
            'message' => "{$import->creadas()} partidas creadas.",
        ]);

        return back();
    }
}
