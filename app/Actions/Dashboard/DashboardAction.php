<?php

namespace App\Actions\Dashboard;

use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Planeacion;
use App\Models\PlaneacionAsignacion;
use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Support\Collection;

class DashboardAction
{
    /**
     * Cuántas semanas hacia atrás (desde la semana más reciente con datos)
     * se muestran en las series de tiempo de Planeación y ejecución.
     */
    private const SEMANAS_VENTANA = 8;

    /**
     * Datos reales del dashboard: Ingenierías (Plantas → Proyectos →
     * Levantamientos → Cotizaciones) y Planeación/ejecución.
     *
     * No se apoya en ninguna tabla ni columna que no exista ya. Cualquier
     * gráfica de la especificación cuyo dato real no exista (ej. horas
     * planeadas vs. ejecutadas, o insumos) simplemente no se calcula aquí;
     * el frontend decide mostrar un estado vacío en su lugar.
     *
     * @return array<string, mixed>
     */
    public function metricas(): array
    {
        return [
            'kpis' => $this->kpis(),
            'proyectosCotizaciones' => [
                'proyectosPorPlanta' => $this->proyectosPorPlanta(),
                'cotizacionesPorPlanta' => $this->cotizacionesPorPlanta(),
                'montoCotizadoPorPlanta' => $this->montoCotizadoPorPlanta(),
            ],
            'planeacionEjecucion' => [
                'actividadesPorSemana' => $this->actividadesPorSemana(),
                'horasPorSemana' => $this->horasPorSemana(),
                'estadoPorSemana' => $this->estadoPorSemana(),
            ],
            'analisisSecundario' => [
                'horasPorEmpleado' => $this->horasPorEmpleado(),
                'actividadesPorPlanta' => $this->actividadesPorPlantaTotal(),
                'montoCotizadoPorProyecto' => $this->montoCotizadoPorProyecto(),
            ],
        ];
    }

    /**
     * KPIs de la fila superior. Sin porcentajes ni crecimientos: solo
     * conteos reales, tal como pide la especificación.
     */
    private function kpis(): array
    {
        return [
            'totalPlantas' => Planta::query()->count(),
            'totalProyectos' => Proyecto::query()->count(),
            'totalLevantamientos' => Levantamiento::query()->count(),
            'totalCotizaciones' => Cotizacion::query()->count(),
            'proyectosActivos' => Proyecto::query()->where('estado', 'activo')->count(),
            'cotizacionesAprobadas' => Cotizacion::query()->where('estado', 'aprobada')->count(),
        ];
    }

    /**
     * TAB 1 de "Proyectos y cotizaciones": ¿qué plantas tienen más proyectos?
     * Cuenta directa de proyectos.planta_id, ordenada de mayor a menor.
     */
    private function proyectosPorPlanta(): array
    {
        return Planta::query()
            ->withCount('proyectos')
            ->orderByDesc('proyectos_count')
            ->get()
            ->map(fn (Planta $p) => [
                'planta' => $p->nombre,
                'valor' => $p->proyectos_count,
            ])
            ->values()
            ->all();
    }

    /**
     * TAB 2: ¿qué plantas concentran más cotizaciones? Una cotización
     * cuelga de proyecto_id siempre (directo o vía levantamiento), así
     * que agrupamos por la planta del proyecto sin importar el origen.
     */
    private function cotizacionesPorPlanta(): array
    {
        return $this->cotizacionesConPlanta()
            ->groupBy('planta_nombre')
            ->map(fn (Collection $grupo, string $planta) => [
                'planta' => $planta,
                'valor' => $grupo->count(),
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();
    }

    /**
     * TAB 3: monto cotizado real por planta (Cotizacion::total, no un
     * "gasto" — es dinero cotizado, no ejecutado).
     */
    private function montoCotizadoPorPlanta(): array
    {
        return $this->cotizacionesConPlanta()
            ->groupBy('planta_nombre')
            ->map(fn (Collection $grupo, string $planta) => [
                'planta' => $planta,
                'valor' => round((float) $grupo->sum('total'), 2),
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();
    }

    /**
     * Monto cotizado real por proyecto (análisis secundario).
     */
    private function montoCotizadoPorProyecto(): array
    {
        return Cotizacion::query()
            ->with('proyecto:id,nombre,folio')
            ->get()
            ->groupBy('proyecto_id')
            ->map(function (Collection $grupo) {
                $proyecto = $grupo->first()->proyecto;

                return [
                    'proyecto' => $proyecto?->nombre ?? $proyecto?->folio ?? 'Sin nombre',
                    'valor' => round((float) $grupo->sum('total'), 2),
                ];
            })
            ->sortByDesc('valor')
            ->values()
            ->take(10)
            ->all();
    }

    /**
     * Todas las cotizaciones con el nombre de la planta de su proyecto ya
     * resuelto, sin importar si vienen de un levantamiento o son directas
     * de proyecto ("chico"). Punto único para no repetir el join.
     */
    private function cotizacionesConPlanta(): Collection
    {
        return Cotizacion::query()
            ->with('proyecto.planta:id,nombre')
            ->get()
            ->map(function (Cotizacion $c) {
                $c->planta_nombre = $c->proyecto?->planta?->nombre ?? 'Sin planta';

                return $c;
            });
    }

    /**
     * TAB 1 de "Planeación y ejecución": actividades (asignaciones reales
     * de PlaneacionAsignacion) por semana, con el rango de fechas real
     * de esa semana ISO como etiqueta — no "S1/S2/S3".
     */
    private function actividadesPorSemana(): array
    {
        return $this->semanasConDatos()
            ->map(fn (Planeacion $p) => $p)
            ->groupBy(fn (Planeacion $p) => "{$p->anio}-{$p->semana}")
            ->map(function (Collection $planeacionesSemana) {
                $referencia = $planeacionesSemana->first();
                $totalActividades = $planeacionesSemana->sum(
                    fn (Planeacion $p) => $p->asignaciones->count()
                );

                return [
                    'anio' => $referencia->anio,
                    'semana' => $referencia->semana,
                    'periodo' => $this->etiquetaPeriodo($referencia),
                    'valor' => $totalActividades,
                ];
            })
            ->sortBy(fn ($fila) => [$fila['anio'], $fila['semana']])
            ->values()
            ->all();
    }

    /**
     * TAB 2: horas reales trabajadas y horas extra por semana. Ambas
     * columnas existen de verdad en planeacion_asignaciones. No existe un
     * campo separado de "horas planeadas", así que no se inventa esa
     * serie — solo se muestran las horas reales registradas.
     */
    private function horasPorSemana(): array
    {
        return $this->semanasConDatos()
            ->groupBy(fn (Planeacion $p) => "{$p->anio}-{$p->semana}")
            ->map(function (Collection $planeacionesSemana) {
                $referencia = $planeacionesSemana->first();
                $asignaciones = $planeacionesSemana->flatMap->asignaciones;

                return [
                    'anio' => $referencia->anio,
                    'semana' => $referencia->semana,
                    'periodo' => $this->etiquetaPeriodo($referencia),
                    'horasTrabajadas' => round((float) $asignaciones->sum('horas_trabajadas'), 2),
                    'horasExtra' => round((float) $asignaciones->sum('horas_extra'), 2),
                ];
            })
            ->sortBy(fn ($fila) => [$fila['anio'], $fila['semana']])
            ->values()
            ->all();
    }

    /**
     * TAB 3: distribución real de actividades por estado
     * (planeacion_asignaciones.estado: asignado, en_progreso, completado,
     * cancelado) apiladas por semana.
     */
    private function estadoPorSemana(): array
    {
        $estadosPosibles = ['asignado', 'en_progreso', 'completado', 'cancelado'];

        return $this->semanasConDatos()
            ->groupBy(fn (Planeacion $p) => "{$p->anio}-{$p->semana}")
            ->map(function (Collection $planeacionesSemana) use ($estadosPosibles) {
                $referencia = $planeacionesSemana->first();
                $asignaciones = $planeacionesSemana->flatMap->asignaciones;
                $conteos = $asignaciones->countBy('estado');

                $fila = [
                    'anio' => $referencia->anio,
                    'semana' => $referencia->semana,
                    'periodo' => $this->etiquetaPeriodo($referencia),
                ];

                foreach ($estadosPosibles as $estado) {
                    $fila[$estado] = $conteos->get($estado, 0);
                }

                return $fila;
            })
            ->sortBy(fn ($fila) => [$fila['anio'], $fila['semana']])
            ->values()
            ->all();
    }

    /**
     * Horas reales trabajadas por empleado (análisis secundario).
     */
    private function horasPorEmpleado(): array
    {
        return PlaneacionAsignacion::query()
            ->with('empleado:id,nombre')
            ->get()
            ->groupBy('empleado_id')
            ->map(function (Collection $grupo) {
                $empleado = $grupo->first()->empleado;

                return [
                    'empleado' => $empleado?->nombre ?? 'Sin nombre',
                    'valor' => round((float) $grupo->sum('horas_trabajadas'), 2),
                ];
            })
            ->sortByDesc('valor')
            ->values()
            ->take(10)
            ->all();
    }

    /**
     * Actividades (asignaciones) totales por planta, vía planeaciones.planta_id
     * (análisis secundario).
     */
    private function actividadesPorPlantaTotal(): array
    {
        return Planeacion::query()
            ->with(['planta:id,nombre', 'asignaciones'])
            ->get()
            ->groupBy(fn (Planeacion $p) => $p->planta?->nombre ?? 'Sin planta')
            ->map(fn (Collection $grupo, string $planta) => [
                'planta' => $planta,
                'valor' => $grupo->sum(fn (Planeacion $p) => $p->asignaciones->count()),
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();
    }

    /**
     * Últimas N semanas (con datos reales) de Planeacion, con sus
     * asignaciones precargadas. Base común para las 3 gráficas de la
     * card de Planeación y ejecución.
     *
     * @return Collection<int, Planeacion>
     */
    private function semanasConDatos(): Collection
    {
        return Planeacion::query()
            ->with('asignaciones')
            ->get()
            // clave numérica (anio*100+semana) para poder ordenar semanas
            // reales sin depender de orden alfabético de strings.
            ->groupBy(fn (Planeacion $p) => ($p->anio * 100) + $p->semana)
            ->sortKeysDesc()
            ->take(self::SEMANAS_VENTANA)
            ->flatten(1);
    }

    /**
     * "03 ago – 09 ago" a partir del rango real de la semana ISO,
     * usando exactamente Planeacion::fechaInicio()/fechaFin().
     */
    private function etiquetaPeriodo(Planeacion $planeacion): string
    {
        $inicio = $planeacion->fechaInicio();
        $fin = $planeacion->fechaFin();

        $formato = fn ($fecha) => $fecha->translatedFormat('d M');

        return mb_strtolower("{$formato($inicio)} – {$formato($fin)}");
    }
}
