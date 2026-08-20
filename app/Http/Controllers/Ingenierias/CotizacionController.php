<?php

namespace App\Http\Controllers\Ingenierias;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Cotizaciones\CotizacionPdfAction;
use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Exports\Cotizaciones\PartidaPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ingenierias\Cotizaciones\ImportCotizacionRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\Partidas\StorePartidaRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\Partidas\UpdatePartidaRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\StoreCotizacionManualRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\UpdateCotizacionRequest;
use App\Http\Requests\Ingenierias\Cotizaciones\UpdateEstatusCompraRequest;
use App\Imports\Cotizaciones\CotizacionExcelImport;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Partida;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CotizacionController extends Controller
{
    public function subirAutorizacionProyecto(Request $request, Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:pdf']]);

        $action->subirPdfAutorizacion($cotizacion, $request->file('archivo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Orden de compra subida.']);

        return back();
    }

    public function ordenCompraProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion): Response
    {
        $pdf = $cotizacion->archivos()->where('tipo_archivo', 'pdf')->latest('fecha_creacion')->first();

        return Inertia::render('ingenierias/plantas/proyectos/cotizaciones/orden-compra/Show', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre],
            'cotizacion' => [
                'id' => $cotizacion->id,
                'folio' => $cotizacion->folio,
                'obra' => $cotizacion->obra,
                'total' => (float) $cotizacion->total,
                'completada' => $cotizacion->estaCompletada(),
                'estatusCompra' => $cotizacion->estatus_compra,
                'fechaEstatus' => $cotizacion->fecha_aprobacion_compra?->format('d/m/Y H:i'),
            ],
            'archivoId' => $pdf?->id,
            'pdfUrl' => $pdf?->urlPublica(),
            'pdfNombre' => $pdf?->nombre_archivo,
            'subidoEl' => $pdf?->fecha_creacion?->format('d/m/Y H:i'),
        ]);
    }

    public function actualizarEstatusCompraProyecto(UpdateEstatusCompraRequest $request, Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->actualizarEstatusCompra($cotizacion, $request->validated('estatus_compra'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estatus de la OC actualizado.']);

        return back();
    }

    public function solicitarRevisionCompraProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->solicitarRevisionSinPdf($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Revisión solicitada.']);

        return back();
    }

    public function aprobarCompraProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->aprobarCompra($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización aprobada.']);

        return back();
    }

    public function rechazarCompraProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->rechazarCompra($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización rechazada.']);

        return back();
    }

    public function ordenCompra(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion): Response
    {
        $pdf = $cotizacion->archivos()->where('tipo_archivo', 'pdf')->latest('fecha_creacion')->first();

        return Inertia::render('ingenierias/plantas/proyectos/levantamientos/cotizaciones/orden-compra/Show', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre],
            'levantamiento' => ['id' => $levantamiento->id, 'folio' => $levantamiento->folio],
            'cotizacion' => [
                'id' => $cotizacion->id,
                'folio' => $cotizacion->folio,
                'obra' => $cotizacion->obra,
                'total' => (float) $cotizacion->total,
                'tieneInsumos' => $cotizacion->tieneInsumos(),
                'completada' => $cotizacion->estaCompletada(),
                'estatusCompra' => $cotizacion->estatus_compra,
                'fechaEstatus' => $cotizacion->fecha_aprobacion_compra?->format('d/m/Y H:i'),
            ],
            'archivoId' => $pdf?->id,
            'pdfUrl' => $pdf?->urlPublica(),
            'pdfNombre' => $pdf?->nombre_archivo,
            'subidoEl' => $pdf?->fecha_creacion?->format('d/m/Y H:i'),
        ]);
    }

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
        $partidas = $partidasAction->arbol($cotizacion);

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
            'partidas' => $partidas,
            'numeroPartidas' => collect($partidas)->sum(fn (array $raiz) => count($raiz['hijas'])),
        ]);
    }

    /**
     * El Excel es la fuente de verdad de la Cotización: crea SIEMPRE una
     * nueva versión, aunque ya exista una cotización previa con la misma
     * obra en este levantamiento. Si el Excel trae errores, no se crea
     * NADA (ni la cotización ni las partidas) — el import es todo o
     * nada, igual que un formulario que no guarda nada si un campo
     * falla.
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

        if (! empty($import->errores())) {
            throw ValidationException::withMessages($this->erroresParaFormulario($import->errores()));
        }

        $cotizacion = $import->cotizacion();

        Inertia::flash('toast', [
            'type' => 'success',
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

    /**
     * Redirige al Levantamiento (padre) en vez de back(): back() apunta al
     * Referer, que es el propio Show de la cotización que se acaba de
     * borrar — visitarlo de nuevo da 404. Mismo fix que
     * LevantamientoController::destroy().
     */
    public function destroy(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->delete($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización eliminada.']);

        return redirect()->route('ingenierias.plantas.proyectos.levantamientos.show', [
            $planta->id, $proyecto->id, $levantamiento->id,
        ]);
    }

    /** Plantilla de partidas — flujo con Levantamiento. */
    public function plantilla(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento): BinaryFileResponse
    {
        return Excel::download(new PartidaPlantillaExport, 'plantilla-partidas.xlsx');
    }

    public function pdf(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionPdfAction $action): HttpResponse
    {
        return $action->generar($cotizacion)->stream("cotizacion-{$cotizacion->folio}.pdf");
    }

    // ---- Flujo de Proyecto directo: cotizaciones colgando del proyecto ----

    public function obraProyecto(Planta $planta, Proyecto $proyecto, string $obra, CotizacionesAction $action): Response
    {
        return Inertia::render('ingenierias/plantas/proyectos/cotizaciones/Obra', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre, 'folio' => $proyecto->folio],
            'grupo' => $action->obraProyecto($proyecto, $obra),
        ]);
    }

    public function showProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action, PartidasAction $partidasAction): Response
    {
        $partidas = $partidasAction->arbol($cotizacion);

        return Inertia::render('ingenierias/plantas/proyectos/cotizaciones/Show', [
            'planta' => ['id' => $planta->id, 'nombre' => $planta->nombre],
            'proyecto' => ['id' => $proyecto->id, 'nombre' => $proyecto->nombre, 'folio' => $proyecto->folio],
            'cotizacion' => $action->detail($cotizacion),
            'partidas' => $partidas,
            'numeroPartidas' => collect($partidas)->sum(fn (array $raiz) => count($raiz['hijas'])),
        ]);
    }

    /**
     * Equivalente a store(), para el flujo de Proyecto directo. Mismo
     * comportamiento todo-o-nada: si el Excel trae errores, no se crea
     * nada y el usuario recibe fila+columna+motivo de cada error.
     */
    public function storeProyecto(
        ImportCotizacionRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        CotizacionesAction $action,
        PartidasAction $partidasAction,
    ): RedirectResponse {
        $import = new CotizacionExcelImport($proyecto, $action, $partidasAction);
        Excel::import($import, $request->file('archivo'));

        if (! empty($import->errores())) {
            throw ValidationException::withMessages($this->erroresParaFormulario($import->errores()));
        }

        $cotizacion = $import->cotizacion();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Cotización creada con {$import->partidasCreadas()} partidas.",
        ]);

        return redirect()->route('ingenierias.plantas.proyectos.cotizaciones.show', [
            $planta->id, $proyecto->id, $cotizacion->id,
        ]);
    }

    /**
     * Captura manual (sin Excel) de una cotización para Proyecto directo.
     * Coexiste con storeProyecto() (Excel): ambas vías crean una versión
     * nueva bajo la misma obra, ninguna reemplaza a la otra.
     */
    public function storeManualProyecto(
        StoreCotizacionManualRequest $request,
        Planta $planta,
        Proyecto $proyecto,
        CotizacionesAction $action,
    ): RedirectResponse {
        $cotizacion = $action->createManualParaProyecto($proyecto, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización creada.']);

        return redirect()->route('ingenierias.plantas.proyectos.cotizaciones.show', [
            $planta->id, $proyecto->id, $cotizacion->id,
        ]);
    }

    public function updateProyecto(UpdateCotizacionRequest $request, Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->update($cotizacion, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización actualizada.']);

        return back();
    }

    /** Mismo fix que destroy(): redirige al Proyecto (padre), no a back(). */
    public function destroyProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->delete($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización eliminada.']);

        return redirect()->route('ingenierias.plantas.proyectos.show', [$planta->id, $proyecto->id]);
    }

    /** Plantilla de partidas — flujo Proyecto directo (sin Levantamiento). */
    public function plantillaProyecto(Planta $planta, Proyecto $proyecto): BinaryFileResponse
    {
        return Excel::download(new PartidaPlantillaExport, 'plantilla-partidas.xlsx');
    }

    public function pdfProyecto(Planta $planta, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionPdfAction $action): HttpResponse
    {
        return $action->generar($cotizacion)->stream("cotizacion-{$cotizacion->folio}.pdf");
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

    public function subirAutorizacion(Request $request, Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:pdf']]);

        $action->subirPdfAutorizacion($cotizacion, $request->file('archivo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Autorización subida.']);

        return back();
    }

    public function actualizarEstatusCompra(UpdateEstatusCompraRequest $request, Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->actualizarEstatusCompra($cotizacion, $request->validated('estatus_compra'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estatus de la OC actualizado.']);

        return back();
    }

    public function solicitarRevisionCompra(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->solicitarRevisionSinPdf($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Revisión solicitada.']);

        return back();
    }

    public function aprobarCompra(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->aprobarCompra($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización aprobada.']);

        return back();
    }

    public function rechazarCompra(Planta $planta, Proyecto $proyecto, Levantamiento $levantamiento, Cotizacion $cotizacion, CotizacionesAction $action): RedirectResponse
    {
        $action->rechazarCompra($cotizacion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización rechazada.']);

        return back();
    }

    /**
     * Convierte los errores del import (fila del Excel => mensajes ya
     * legibles "Partida X, columna Y: motivo") al formato de errores de
     * un FormRequest, para que ValidationException los mande igual que
     * cualquier otro error de formulario y el frontend los lea de
     * page.props.errors sin ningún mecanismo especial.
     *
     * @param  array<int, array<int, string>>  $errores
     * @return array<string, string>
     */
    private function erroresParaFormulario(array $errores): array
    {
        $mensajes = [];

        foreach ($errores as $fila => $erroresFila) {
            foreach ($erroresFila as $indice => $mensaje) {
                $mensajes["archivo_fila_{$fila}_{$indice}"] = $fila > 0 ? "Fila {$fila}: {$mensaje}" : $mensaje;
            }
        }

        return $mensajes;
    }
}
