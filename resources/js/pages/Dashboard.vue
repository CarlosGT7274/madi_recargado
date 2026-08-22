<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2, ClipboardList, FileStack, FileText } from '@lucide/vue';
import { ref } from 'vue';
import ActividadesAreaChart from '@/components/dashboard/charts/apex/ActividadesAreaChart.vue';
import EstadoStackedBarChart from '@/components/dashboard/charts/apex/EstadoStackedBarChart.vue';
import HorasBarChart from '@/components/dashboard/charts/apex/HorasBarChart.vue';
import PlantaBarChart from '@/components/dashboard/charts/apex/PlantaBarChart.vue';
import DashboardTabs from '@/components/dashboard/DashboardTabs.vue';
import EmptyChartState from '@/components/dashboard/EmptyChartState.vue';
import KpiCard from '@/components/dashboard/KpiCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

// ---------------------------------------------------------------
// Tipos — reflejan exactamente el shape de DashboardAction::metricas()
// ---------------------------------------------------------------

type FilaPlanta = { planta: string; valor: number };
type FilaProyecto = { proyecto: string; valor: number };
type FilaEmpleado = { empleado: string; valor: number };

type FilaActividadSemana = { anio: number; semana: number; periodo: string; valor: number };
type FilaHorasSemana = {
    anio: number;
    semana: number;
    periodo: string;
    horasTrabajadas: number;
    horasExtra: number;
};
type FilaEstadoSemana = {
    anio: number;
    semana: number;
    periodo: string;
    asignado: number;
    en_progreso: number;
    completado: number;
    cancelado: number;
};

type Metricas = {
    kpis: {
        totalPlantas: number;
        totalProyectos: number;
        totalLevantamientos: number;
        totalCotizaciones: number;
        proyectosActivos: number;
        cotizacionesAprobadas: number;
    };
    proyectosCotizaciones: {
        proyectosPorPlanta: FilaPlanta[];
        cotizacionesPorPlanta: FilaPlanta[];
        montoCotizadoPorPlanta: FilaPlanta[];
    };
    planeacionEjecucion: {
        actividadesPorSemana: FilaActividadSemana[];
        horasPorSemana: FilaHorasSemana[];
        estadoPorSemana: FilaEstadoSemana[];
    };
    // Se sigue recibiendo del backend para cuando el Dashboard crezca con
    // otras secciones; hoy no se renderiza (solo 2 cards principales).
    analisisSecundario: {
        horasPorEmpleado: FilaEmpleado[];
        actividadesPorPlanta: FilaPlanta[];
        montoCotizadoPorProyecto: FilaProyecto[];
    };
};

const props = defineProps<{ metricas: Metricas }>();

function moneda(valor: number): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(valor);
}

// ---------------------------------------------------------------
// Card 1 — Proyectos y cotizaciones
// ---------------------------------------------------------------

const tabsProyectosCotizaciones = [
    { key: 'proyectos', label: 'Proyectos' },
    { key: 'cotizaciones', label: 'Cotizaciones' },
    { key: 'monto', label: 'Monto cotizado' },
];
const tabProyectosCotizaciones = ref<'proyectos' | 'cotizaciones' | 'monto'>('proyectos');

const hayMontoCotizado = props.metricas.proyectosCotizaciones.montoCotizadoPorPlanta.length > 0;

const tituloProyectosCotizaciones: Record<string, string> = {
    proyectos: 'Proyectos registrados por planta',
    cotizaciones: 'Cotizaciones registradas por planta',
    monto: 'Monto cotizado por planta',
};
const subtituloProyectosCotizaciones: Record<string, string> = {
    proyectos: '¿Qué plantas tienen más proyectos?',
    cotizaciones: '¿Qué plantas tienen más cotizaciones?',
    monto: '¿Qué plantas concentran mayor monto cotizado?',
};

// ---------------------------------------------------------------
// Card 2 — Planeación y ejecución
// ---------------------------------------------------------------

const tabsPlaneacionEjecucion = [
    { key: 'actividades', label: 'Actividades' },
    { key: 'horas', label: 'Horas' },
    { key: 'estado', label: 'Estado' },
];
const tabPlaneacionEjecucion = ref<'actividades' | 'horas' | 'estado'>('actividades');

const haySemanasConDatos = props.metricas.planeacionEjecucion.actividadesPorSemana.length > 0;

const tituloPlaneacionEjecucion: Record<string, string> = {
    actividades: 'Actividades planeadas por semana',
    horas: 'Horas trabajadas por semana',
    estado: 'Estado de las actividades por semana',
};
const subtituloPlaneacionEjecucion: Record<string, string> = {
    actividades: '¿Cómo evolucionan las actividades planeadas a lo largo del tiempo?',
    horas: '¿Cuántas horas de trabajo están siendo planeadas?',
    estado: '¿Cómo se distribuyen las actividades según su estado?',
};
</script>

<template>

    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div>
            <h1 class="text-xl font-semibold">Resumen general</h1>
            <p class="text-sm text-muted-foreground">
                Plantas, proyectos, cotizaciones y ejecución de planeación de un vistazo.
            </p>
        </div>

        <!-- KPIs -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-4">
            <KpiCard :valor="metricas.kpis.totalPlantas" etiqueta="Plantas" :icono="Building2" />
            <KpiCard :valor="metricas.kpis.totalProyectos" etiqueta="Proyectos"
                :descripcion="`${metricas.kpis.proyectosActivos} activos`" :icono="FileStack" />
            <KpiCard :valor="metricas.kpis.totalLevantamientos" etiqueta="Levantamientos" :icono="ClipboardList" />
            <KpiCard :valor="metricas.kpis.totalCotizaciones" etiqueta="Cotizaciones"
                :descripcion="`${metricas.kpis.cotizacionesAprobadas} aprobadas`" :icono="FileText" />
        </div>

        <!-- Dos cards principales -->
        <div class="grid gap-4 xl:grid-cols-2">
            <!-- Card 1: Proyectos y cotizaciones -->
            <Card class="flex flex-col">
                <CardHeader class="gap-3">
                    <div>
                        <CardTitle class="text-base">Proyectos y cotizaciones</CardTitle>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            {{ tituloProyectosCotizaciones[tabProyectosCotizaciones] }}
                        </p>
                    </div>
                    <DashboardTabs :tabs="tabsProyectosCotizaciones" :activo="tabProyectosCotizaciones"
                        @update:activo="(k) => (tabProyectosCotizaciones = k as typeof tabProyectosCotizaciones)" />
                    <p class="text-xs text-muted-foreground">
                        {{ subtituloProyectosCotizaciones[tabProyectosCotizaciones] }}
                    </p>
                </CardHeader>
                <CardContent class="h-[420px] flex-1">
                    <PlantaBarChart v-if="tabProyectosCotizaciones === 'proyectos'"
                        :data="metricas.proyectosCotizaciones.proyectosPorPlanta" color="hsl(var(--chart-1))" />
                    <PlantaBarChart v-else-if="tabProyectosCotizaciones === 'cotizaciones'"
                        :data="metricas.proyectosCotizaciones.cotizacionesPorPlanta" color="hsl(var(--chart-2))" />
                    <EmptyChartState v-else-if="!hayMontoCotizado"
                        detalle="No hay cotizaciones con monto capturado todavía. En cuanto se registren montos en las cotizaciones, esta gráfica se llenará automáticamente." />
                    <PlantaBarChart v-else :data="metricas.proyectosCotizaciones.montoCotizadoPorPlanta"
                        color="hsl(var(--chart-3))" :value-formatter="moneda" />
                </CardContent>
            </Card>

            <!-- Card 2: Planeación y ejecución -->
            <Card class="flex flex-col">
                <CardHeader class="gap-3">
                    <div>
                        <CardTitle class="text-base">Planeación y ejecución</CardTitle>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            {{ tituloPlaneacionEjecucion[tabPlaneacionEjecucion] }}
                        </p>
                    </div>
                    <DashboardTabs :tabs="tabsPlaneacionEjecucion" :activo="tabPlaneacionEjecucion"
                        @update:activo="(k) => (tabPlaneacionEjecucion = k as typeof tabPlaneacionEjecucion)" />
                    <p class="text-xs text-muted-foreground">
                        {{ subtituloPlaneacionEjecucion[tabPlaneacionEjecucion] }}
                    </p>
                </CardHeader>
                <CardContent class="h-[420px] flex-1">
                    <EmptyChartState v-if="!haySemanasConDatos"
                        detalle="Todavía no hay planeaciones semanales registradas. Esta sección se activa en cuanto exista al menos una planeación con asignaciones." />
                    <ActividadesAreaChart v-else-if="tabPlaneacionEjecucion === 'actividades'"
                        :data="metricas.planeacionEjecucion.actividadesPorSemana" />
                    <HorasBarChart v-else-if="tabPlaneacionEjecucion === 'horas'"
                        :data="metricas.planeacionEjecucion.horasPorSemana" />
                    <EstadoStackedBarChart v-else :data="metricas.planeacionEjecucion.estadoPorSemana" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
