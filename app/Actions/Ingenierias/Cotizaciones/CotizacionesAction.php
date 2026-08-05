<?php

namespace App\Actions\Ingenierias\Cotizaciones;

use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Services\FolioService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CotizacionesAction
{
    public function __construct(
        private readonly FolioService $folios,
        private readonly PartidasAction $partidasAction,
    ) {}

    /**
     * Agrupa las cotizaciones del levantamiento por nombre de obra. Cada
     * grupo es "un mismo trabajo cotizado varias veces"; cada fila dentro
     * del grupo es una versión (un Excel subido). Única fuente de la lista
     * de obras — la consume Levantamiento/Show.vue.
     */
    public function listAgrupado(Levantamiento $levantamiento): Collection
    {
        return $levantamiento->cotizaciones()
            ->with('archivos', 'ordenCompra.archivos', 'insumos')
            ->latest('id')
            ->get()
            ->groupBy(fn (Cotizacion $c) => $c->obra ?: 'Sin nombre de obra')
            ->map(function (Collection $versiones, string $obra) {
                $ultima = $versiones->first();

                return [
                    'obra' => $obra,
                    'totalVersiones' => $versiones->count(),
                    'completada' => $versiones->contains(fn (Cotizacion $c) => $c->estaCompletada()),
                    'ultimaVersion' => $this->resumenVersion($ultima),
                ];
            })
            ->values();
    }

    /**
     * Única pantalla intermedia del flujo: todas las versiones de una obra
     * puntual, planas, sin agrupar más.
     */
    public function obra(Levantamiento $levantamiento, string $obra): array
    {
        $versiones = $levantamiento->cotizaciones()
            ->with('archivos', 'ordenCompra.archivos', 'insumos')
            ->where('obra', $obra)
            ->latest('id')
            ->get();

        $completada = $versiones->first(fn (Cotizacion $c) => $c->estaCompletada());

        return [
            'obra' => $obra,
            'completada' => $completada !== null,
            'montoCompletado' => $completada?->total,
            'totalVersiones' => $versiones->count(),
            'versiones' => $versiones->map(fn (Cotizacion $c) => $this->resumenVersion($c))->values(),
        ];
    }

    private function resumenVersion(Cotizacion $c): array
    {
        return [
            'id' => $c->id,
            'folio' => $c->folio,
            'fecha' => $c->fecha?->format('d/m/Y'),
            'cliente' => $c->cliente,
            'vendedor' => $c->vendedor,
            'total' => $c->total,
            'estado' => $c->estado,
            'completada' => $c->estaCompletada(),
            'tienePartidas' => $c->tiene_partidas,
            'tieneInsumos' => $c->tieneInsumos(),
            'tieneOrdenAprobada' => $c->tieneOrdenAprobada(),
            'archivoExcelUrl' => $c->archivos
                ->where('tipo_archivo', 'excel')
                ->sortByDesc('fecha_creacion')
                ->first()
                ?->urlPublica(),
        ];
    }

    public function detail(Cotizacion $cotizacion): array
    {
        return [
            'id' => $cotizacion->id,
            'levantamiento_id' => $cotizacion->levantamiento_id,
            'folio' => $cotizacion->folio,
            'fecha' => $cotizacion->fecha?->format('d/m/Y'),
            'para' => $cotizacion->para,
            'cliente' => $cotizacion->cliente,
            'direccion' => $cotizacion->direccion,
            'obra' => $cotizacion->obra,
            'vendedor' => $cotizacion->vendedor,
            'proveedor' => $cotizacion->proveedor,
            'correo_vendedor' => $cotizacion->correo_vendedor,
            'subtotal' => $cotizacion->subtotal,
            'iva' => $cotizacion->iva,
            'total' => $cotizacion->total,
            'moneda' => $cotizacion->moneda,
            'tiempo_entrega' => $cotizacion->tiempo_entrega,
            'dias_credito' => $cotizacion->dias_credito,
            'vigencia_cotizacion' => $cotizacion->vigencia_cotizacion,
            'notas' => $cotizacion->notas,
            'estado' => $cotizacion->estado,
            'creado' => $cotizacion->fecha_creacion?->format('d/m/Y H:i'),
            'modificado' => $cotizacion->fecha_modificacion?->format('d/m/Y H:i'),
            'tiene_insumos' => $cotizacion->tieneInsumos(),
            'tiene_orden_compra' => $cotizacion->tieneOrdenAprobada(),
            'completada' => $cotizacion->estaCompletada(),
            'otrasVersiones' => $this->versionesDeObra($cotizacion->levantamiento, $cotizacion->obra ?? '')
                ->reject(fn (array $v) => $v['id'] === $cotizacion->id)
                ->values(),
            'ordenCompra' => $cotizacion->ordenCompra ? [
                'id' => $cotizacion->ordenCompra->id,
                'estatusCompra' => $cotizacion->ordenCompra->estatus_compra,
                'pdfUrl' => $cotizacion->ordenCompra->pdf()?->urlPublica(),
                'pdfNombre' => $cotizacion->ordenCompra->pdf()?->nombre_archivo,
            ] : null,
        ];
    }

    public function versionesDeObra(Levantamiento $levantamiento, string $obra): Collection
    {
        return $levantamiento->cotizaciones()
            ->with('archivos', 'ordenCompra.archivos', 'insumos')
            ->where('obra', $obra)
            ->latest('id')
            ->get()
            ->map(fn (Cotizacion $c) => $this->resumenVersion($c));
    }

    /**
     * Crea SIEMPRE una nueva versión. El Excel es la fuente de verdad:
     * si ya existe una cotización con la misma obra en este levantamiento,
     * esta se agrega como versión nueva, nunca reemplaza a la anterior.
     */
    public function create(Levantamiento $levantamiento, array $data): Cotizacion
    {
        $data['folio'] = $this->folios->siguiente('ingenierias', 'cotizacion', 'COT');

        return $levantamiento->cotizaciones()->create([
            ...$data,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function update(Cotizacion $cotizacion, array $data): Cotizacion
    {
        $cotizacion->update($data);

        if (array_key_exists('iva', $data)) {
            $this->partidasAction->recalcularTotales($cotizacion);
        }

        return $cotizacion;
    }

    public function delete(Cotizacion $cotizacion): void
    {
        $cotizacion->delete();
    }
}
