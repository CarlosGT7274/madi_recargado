<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Exports\Cotizaciones\PartidaPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Cotizaciones\ImportCotizacionRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\Partidas\StorePartidaRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\Partidas\UpdatePartidaRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\UpdateCotizacionRequest;
use App\Imports\Cotizaciones\CotizacionExcelImport;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Partida;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CotizacionController extends Controller
{
    /**
     * Única pantalla intermedia del flujo: versiones (Excel) de UNA obra.
     * El listado de obras agrupadas vive en Levantamiento/Show.vue.
     */
    public function obra(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, string $obra, CotizacionesAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/Obra', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre, 'folio' => $proyecto->folio],
            'levantamiento' => ['id' => $levantamiento->id, 'folio' => $levantamiento->folio],
            'grupo' => $action->obra($levantamiento, $obra),
        ]);
    }

    public function show(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action, PartidasAction $partidasAction): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/Show', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'folio' => $proyecto->folio,
                'completado' => $proyecto->estaCompletado(),
            ],
            'levantamiento' => ['id' => $levantamiento->id, 'folio' => $levantamiento->folio],
            'cotizacion' => $action->detail($cotizacion),
            'partidas' => $partidasAction->arbol($cotizacion),
        ]);
    }

    /**
     * El Excel es la fuente de verdad de la Cotización: crea SIEMPRE una
     * nueva versión, aunque ya exista una cotización previa con la misma
     * obra en este levantamiento.
     */
    public function store(
        ImportCotizacionRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        CotizacionesAction $action,
        PartidasAction $partidasAction,
    ): RedirectResponse {
        $import = new CotizacionExcelImport($levantamiento, $action, $partidasAction);
        Excel::import($import, $request->file('archivo'));

        $cotizacion = $import->cotizacion();

        Inertia::flash('toast', [
            'type' => empty($import->errores()) ? 'success' : 'warning',
            'message' => "Cotización creada con {$import->partidasCreadas()} partidas.",
        ]);

        return redirect()->route('ingenierias.plantas.proyectos.levantamientos.cotizaciones.show', [
            $planta->id, $proyecto->id, $levantamiento->id, $cotizacion->id,
        ]);
    }

    public function update(UpdateCotizacionRequest $request, Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->update($cotizacion, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización actualizada.']);

        return back();
    }

    public function destroy(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->delete($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización eliminada.']);

        return back();
    }

    public function plantilla(): BinaryFileResponse
    {
        return Excel::download(new PartidaPlantillaExport, 'plantilla-partidas.xlsx');
    }

    // ---- Partidas: sub-acciones de Cotización, no un módulo aparte ----

    public function storePartida(StorePartidaRequest $request, Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, PartidasAction $action): RedirectResponse
    {
        $action->create($cotizacion, $request->validated());

        return back();
    }

    public function updatePartida(UpdatePartidaRequest $request, Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, Partida $partida, PartidasAction $action): RedirectResponse
    {
        $action->update($partida, $request->validated());

        return back();
    }

    public function destroyPartida(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, Partida $partida, PartidasAction $action): RedirectResponse
    {
        $action->delete($partida);

        return back();
    }
}
