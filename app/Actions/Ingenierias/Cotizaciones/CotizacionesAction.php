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
     * Lista plana de cotizaciones de un levantamiento (sin agrupar por obra).
     * Usada por el preview de Levantamiento::show, donde solo se muestran
     * tarjetas sueltas y no hace falta la jerarquía de versiones.
     */
    public function list(Levantamiento $levantamiento): Collection
    {
        return $levantamiento->cotizaciones()
            ->with('archivos')
            ->latest('id')
            ->get()
            ->map(fn (Cotizacion $c) => $this->resumenVersion($c));
    }

    /**
     * Vista intermedia: agrupa las cotizaciones del levantamiento por
     * nombre de obra. Cada grupo es "un mismo trabajo cotizado varias
     * veces"; cada fila dentro del grupo es una versión (un Excel subido).
     */
    public function listAgrupado(Levantamiento $levantamiento): Collection
    {
        return $levantamiento->cotizaciones()
            ->with('archivos')
            ->latest('id')
            ->get()
            ->groupBy(fn (Cotizacion $c) => $c->obra ?: 'Sin nombre de obra')
            ->map(function (Collection $versiones, string $obra) {
                $ultima = $versiones->first(); // ya viene ordenado desc por latest('id')

                return [
                    'obra' => $obra,
                    'totalVersiones' => $versiones->count(),
                    'ultimaVersion' => $this->resumenVersion($ultima),
                    'versiones' => $versiones->map(fn (Cotizacion $c) => $this->resumenVersion($c))->values(),
                ];
            })
            ->values();
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
            'tienePartidas' => $c->tiene_partidas,
            'tieneInsumos' => $c->tieneInsumos(),
            'archivoExcelUrl' => $c->archivos
                ->where('tipo_archivo', 'excel')
                ->sortByDesc('fecha_creacion')
                ->first()
                ?->urlPublica(),
        ];
    }

    /**
     * Todas las versiones de una obra específica dentro de un levantamiento.
     * Usado por la ruta `.../cotizaciones/{obra}` (versiones).
     */
    public function versionesDeObra(Levantamiento $levantamiento, string $obra): Collection
    {
        return $levantamiento->cotizaciones()
            ->with('archivos')
            ->where('obra', $obra)
            ->latest('id')
            ->get()
            ->map(fn (Cotizacion $c) => $this->resumenVersion($c));
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
            'creado' => $cotizacion->created_at?->format('d/m/Y H:i'),
            'modificado' => $cotizacion->updated_at?->format('d/m/Y H:i'),
            'tiene_insumos' => $cotizacion->tieneInsumos(),
            'tiene_orden_compra' => $cotizacion->tieneOrdenAprobada(),
            'completada' => $cotizacion->estaCompletada(),
            'otrasVersiones' => $this->versionesDeObra($cotizacion->levantamiento, $cotizacion->obra ?? '')
                ->reject(fn (array $v) => $v['id'] === $cotizacion->id)
                ->values(),
        ];
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
        $aprobandoAhora = ($data['estado'] ?? null) === 'aprobada' && $cotizacion->estado !== 'aprobada';

        if ($aprobandoAhora) {
            $data['fecha_aprobacion'] = now()->toDateString();
        }

        $cotizacion->update($data);

        if ($aprobandoAhora) {
            $cotizacion->levantamiento->update([
                'fecha_cotizacion_enviada' => now()->toDateString(),
            ]);
        }

        if (array_key_exists('iva', $data)) {
            $this->partidasAction->recalcularTotales($cotizacion);
        }

        return $cotizacion;
    }

    public function delete(Cotizacion $cotizacion): void
    {
        $cotizacion->delete();
    }

    public function resumen(Levantamiento $levantamiento): array
    {
        $cotizaciones = $levantamiento->cotizaciones()->get();
        $aprobadas = $cotizaciones->where('estado', 'aprobada');

        $yaEnviada = $levantamiento->fecha_cotizacion_enviada !== null;
        $programada = $levantamiento->fecha_envio_cotizacion_programada;

        $tiempoRestanteHoras = null;

        if (! $yaEnviada && $programada !== null) {
            $horasAbs = (int) round(now()->diffInHours($programada));
            $tiempoRestanteHoras = $programada->isFuture() ? $horasAbs : -$horasAbs;
        }

        return [
            'totalCotizaciones' => $cotizaciones->count(),
            'totalObras' => $cotizaciones->pluck('obra')->unique()->count(),
            'totalAprobadas' => $aprobadas->count(),
            'montoTotalAprobado' => (float) $aprobadas->sum('total'),
            'tiempoRestanteHoras' => $tiempoRestanteHoras,
            'yaEnviada' => $yaEnviada,
        ];
    }
}
