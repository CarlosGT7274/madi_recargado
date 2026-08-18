<?php

namespace App\Actions\Dashboard;

use App\Models\Cotizacion;
use App\Models\Proyecto;
use Illuminate\Support\Carbon;

class DashboardAction
{
    /**
     * Métricas generales del sistema para la primera versión del Dashboard.
     *
     * Sólo se apoya en datos y relaciones que ya existen (cotizaciones,
     * insumos, proyectos). No inventa columnas ni tablas nuevas.
     *
     * @return array<string, mixed>
     */
    public function metricas(): array
    {
        // Cotizaciones con insumos y archivos precargados para reutilizar
        // estaCompletada() sin disparar consultas extra por cada registro.
        $cotizaciones = Cotizacion::query()
            ->with([
                'insumos' => fn ($q) => $q->where('activo', true),
                'archivos',
            ])
            ->get();

        $ingresoConInsumos = 0.0;   // total de cotizaciones que ya tienen explosión de insumos
        $costoInsumos = 0.0;        // costo de insumos con IVA (misma base que InsumosAction::resumen)
        $montoPorFacturar = 0.0;    // total de cotizaciones completadas/aprobadas
        $cotizacionesCompletadas = 0;

        foreach ($cotizaciones as $cotizacion) {
            if ($cotizacion->estaCompletada()) {
                $montoPorFacturar += (float) $cotizacion->total;
                $cotizacionesCompletadas++;
            }

            // La utilidad bruta sólo puede calcularse donde ya existe costo,
            // es decir, cotizaciones con insumos cargados.
            if ($cotizacion->insumos->isNotEmpty()) {
                $subtotalInsumos = (float) $cotizacion->insumos->sum('importe');
                $ingresoConInsumos += (float) $cotizacion->total;
                $costoInsumos += round($subtotalInsumos * (1 + Cotizacion::IVA_PORCENTAJE), 2);
            }
        }

        $utilidadBruta = round($ingresoConInsumos - $costoInsumos, 2);
        $margen = $ingresoConInsumos > 0
            ? round(($utilidadBruta / $ingresoConInsumos) * 100, 1)
            : null;

        // Proyectos grandes inconclusos: activos que todavía no llegan a la
        // explosión de insumos + orden de compra (estaCompletado() == false).
        $proyectosGrandes = Proyecto::query()
            ->where('tipo', 'grande')
            ->where('estado', 'activo')
            ->with('planta')
            ->latest('fecha_creacion')
            ->get();

        $inconclusos = $proyectosGrandes->reject(
            fn (Proyecto $p) => $p->estaCompletado()
        );

        return [
            'utilidadBruta' => $utilidadBruta,
            'margen' => $margen,
            'montoPorFacturar' => $montoPorFacturar,
            'cotizacionesCompletadas' => $cotizacionesCompletadas,
            'totalCotizaciones' => $cotizaciones->count(),
            'proyectosInconclusos' => [
                'total' => $inconclusos->count(),
                'items' => $inconclusos->take(6)->map(fn (Proyecto $p) => [
                    'id' => $p->id,
                    'planta_id' => $p->planta_id,
                    'folio' => $p->folio,
                    'nombre' => $p->nombre,
                    'planta' => $p->planta?->nombre,
                    'creado' => $p->fecha_creacion
                        ? Carbon::parse($p->fecha_creacion)->format('d/m/Y')
                        : null,
                ])->values(),
            ],
        ];
    }
}
