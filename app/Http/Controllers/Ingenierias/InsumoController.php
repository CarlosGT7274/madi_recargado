<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Insumos\InsumosAction;
use App\Exports\Ingenierias\Insumos\InsumoPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Insumos\ImportInsumosRequest;
use App\Imports\Ingenierias\Insumos\InsumosExcelImport;
use App\Models\Cotizacion;
use App\Models\Insumo;
use App\Models\Levantamiento;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Support\Ingenierias\Insumos\InsumoParserResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InsumoController extends Controller
{
    public function index(
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        InsumosAction $action,
    ): Response {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/insumos/Index', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre],
            'levantamiento' => ['id' => $levantamiento->id, 'folio' => $levantamiento->folio],
            'cotizacion' => [
                'id' => $cotizacion->id,
                'folio' => $cotizacion->folio,
                'total' => (float) $cotizacion->total,
            ],
            'insumos' => $action->list($cotizacion),
            'resumen' => $action->resumen($cotizacion),
        ]);
    }

    public function plantilla(): BinaryFileResponse
    {
        return Excel::download(new InsumoPlantillaExport, 'plantilla-insumos.xlsx');
    }

    public function importar(
        ImportInsumosRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
    ): RedirectResponse {
        $parser = InsumoParserResolver::resolver($request->validated('tipo_plantilla') ?? 'propia');
        $import = new InsumosExcelImport($cotizacion, $parser);
        Excel::import($import, $request->file('archivo'));

        Inertia::flash('toast', [
            'type' => empty($import->errores()) ? 'success' : 'warning',
            'message' => "{$import->creados()} insumos importados.",
        ]);

        return back();
    }

    public function destroy(
        Planta $planta,
        Proyecto $proyecto,
        Levantamiento $levantamiento,
        Cotizacion $cotizacion,
        Insumo $insumo,
        InsumosAction $action,
    ): RedirectResponse {
        $action->delete($insumo);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Insumo eliminado.']);

        return back();
    }
}
