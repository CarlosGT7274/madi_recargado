<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Exports\LevantamientoPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\ImportLevantamientosRequest;
use App\Http\Requests\Ingenierias\StoreLevantamientoRequest;
use App\Http\Requests\Ingenierias\UpdateLevantamientoRequest;
use App\Imports\LevantamientosImport;
use App\Models\Levantamiento;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LevantamientoController extends Controller
{
    public function index(Planta $planta, Proyecto $proyecto, LevantamientosAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/Index', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
                'folio' => $planta->folio,
            ],
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'folio' => $proyecto->folio,
            ],
            'levantamientos' => $action->list($proyecto),
        ]);
    }

    public function data(Planta $planta, Proyecto $proyecto, LevantamientosAction $action)
    {
        return response()->json($action->list($proyecto));
    }

    public function show(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, LevantamientosAction $action, CotizacionesAction $cotizacionesAction): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/Show', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
            ],
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'folio' => $proyecto->folio,
            ],
            'levantamiento' => $action->detail($levantamiento),
            'cotizaciones' => Inertia::defer(
                fn () => $cotizacionesAction->list($levantamiento)
            ),
            'obras' => Inertia::defer(
                fn () => app(CotizacionesAction::class)->listAgrupado($levantamiento)
            ),
        ]);
    }

    public function store(StoreLevantamientoRequest $request, Planta $planta, Proyecto $proyecto, LevantamientosAction $action): RedirectResponse
    {
        $levantamiento = $action->create($proyecto, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento creado.']);

        return redirect()->route('ingenierias.plantas.proyectos.levantamientos.show', [
            $planta->id, $proyecto->id, $levantamiento->id,
        ]);
    }

    public function update(UpdateLevantamientoRequest $request, Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, LevantamientosAction $action): RedirectResponse
    {
        $action->update($levantamiento, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento actualizado.']);

        return back();
    }

    public function destroy(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, LevantamientosAction $action): RedirectResponse
    {
        $action->delete($levantamiento);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Levantamiento eliminado.']);

        return back();
    }

    public function create(Planta $planta, Proyecto $proyecto): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/Create', [
            'planta' => [
                'id' => $planta->id,
                'nombre' => $planta->nombre,
                'folio' => $planta->folio,
            ],
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'folio' => $proyecto->folio,
            ],
        ]);
    }

    public function plantilla(Planta $planta, Proyecto $proyecto): BinaryFileResponse
    {
        return Excel::download(
            new LevantamientoPlantillaExport,
            "plantilla-levantamientos-{$planta->folio}.xlsx",
        );
    }

    public function importar(
        ImportLevantamientosRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        LevantamientosAction $action,
    ): RedirectResponse {
        $import = new LevantamientosImport($proyecto, $action);
        Excel::import($import, $request->file('archivo'));

        if (! empty($import->errores())) {
            $mensajes = [];
            foreach ($import->errores() as $fila => $erroresFila) {
                $mensajes["fila_{$fila}"] = "Fila {$fila}: ".implode(' ', $erroresFila);
            }

            throw ValidationException::withMessages($mensajes);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$import->creados()} levantamientos creados.",
        ]);

        // Si solo se creó uno, hay un destino único y obvio: su detalle.
        // Si se crearon varios, no hay "un" destino, así que nos quedamos aquí.
        if ($import->creados() === 1) {
            $levantamiento = $import->creadosModelos()[0];

            return redirect()->route('ingenierias.plantas.proyectos.levantamientos.show', [
                $planta->id, $proyecto->id, $levantamiento->id,
            ]);
        }

        return back();
    }
}
