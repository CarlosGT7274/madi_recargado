<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Cotizaciones\ImportCotizacionRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\UpdateCotizacionRequest;
use App\Imports\Cotizaciones\CotizacionExcelImport;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class CotizacionController extends Controller
{
    public function show(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action, PartidasAction $partidasAction): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/Show', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
            ],
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'folio' => $proyecto->folio,
                'completado' => $proyecto->estaCompletado(),
            ],
            'levantamiento' => [
                'id' => $levantamiento->id,
                'folio' => $levantamiento->folio,
            ],
            'cotizacion' => $action->detail($cotizacion),
            'partidas' => $partidasAction->list($cotizacion),
        ]);
    }

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

    public function index(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, CotizacionesAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/Index', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre, 'folio' => $proyecto->folio],
            'levantamiento' => [
                'id' => $levantamiento->id,
                'folio' => $levantamiento->folio,
                'nombre' => $levantamiento->nombre,
                'cliente' => $levantamiento->cliente,
                'estatus_admin' => $levantamiento->estatus_admin,
                'creado' => $levantamiento->fecha_creacion?->format('d M Y'),
            ],
            'resumen' => $action->resumen($levantamiento),
            'cotizaciones' => $action->list($levantamiento),
        ]);
    }
}
