<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowRight,
    Building2,
    FolderOpen,
    Hourglass,
    Receipt,
    TrendingUp,
} from '@lucide/vue';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

type ProyectoInconcluso = {
    id: number;
    planta_id: number;
    folio: string;
    nombre: string | null;
    planta: string | null;
    creado: string | null;
};

type Metricas = {
    utilidadBruta: number;
    margen: number | null;
    montoPorFacturar: number;
    cotizacionesCompletadas: number;
    totalCotizaciones: number;
    proyectosInconclusos: {
        total: number;
        items: ProyectoInconcluso[];
    };
};

const props = defineProps<{ metricas: Metricas }>();

function moneda(valor: number): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(valor);
}

const margenLabel = computed(() =>
    props.metricas.margen === null ? '—' : `${props.metricas.margen}%`,
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div>
            <h1 class="text-xl font-semibold">Resumen general</h1>
            <p class="text-sm text-muted-foreground">
                Indicadores clave del sistema de un vistazo.
            </p>
        </div>

        <!-- Métricas principales -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Utilidad bruta general
                    </CardTitle>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                        <TrendingUp class="size-5" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold tabular-nums">
                        {{ moneda(metricas.utilidadBruta) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Margen estimado: {{ margenLabel }} · sobre cotizaciones con insumos
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Monto por facturar
                    </CardTitle>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600">
                        <Receipt class="size-5" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold tabular-nums">
                        {{ moneda(metricas.montoPorFacturar) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ metricas.cotizacionesCompletadas }} de
                        {{ metricas.totalCotizaciones }} cotizaciones aprobadas
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Proyectos inconclusos
                    </CardTitle>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                        <Hourglass class="size-5" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold tabular-nums">
                        {{ metricas.proyectosInconclusos.total }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Proyectos mayores sin explosión de insumos ni orden de compra
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Detalle: proyectos inconclusos -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <FolderOpen class="size-5 text-muted-foreground" />
                    Proyectos mayores por concluir
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul v-if="metricas.proyectosInconclusos.items.length" class="divide-y">
                    <li
                        v-for="pry in metricas.proyectosInconclusos.items"
                        :key="pry.id"
                    >
                        <Link
                            :href="ProyectoController.show([pry.planta_id, pry.id])"
                            class="group flex items-center gap-3 py-3 transition-colors hover:bg-accent/50"
                        >
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                                <Building2 class="size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ pry.folio }} — {{ pry.nombre ?? 'Sin nombre' }}
                                </p>
                                <p class="truncate text-sm text-muted-foreground">
                                    {{ pry.planta ?? 'Sin planta' }} · Creado {{ pry.creado ?? '—' }}
                                </p>
                            </div>
                            <ArrowRight
                                class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                            />
                        </Link>
                    </li>
                </ul>

                <div
                    v-else
                    class="flex flex-col items-center gap-2 py-10 text-center"
                >
                    <FolderOpen class="size-8 text-muted-foreground" />
                    <p class="text-sm font-medium">No hay proyectos mayores pendientes</p>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        Todos los proyectos mayores activos ya cuentan con explosión de
                        insumos y orden de compra.
                    </p>
                </div>

                <p
                    v-if="
                        metricas.proyectosInconclusos.total >
                        metricas.proyectosInconclusos.items.length
                    "
                    class="mt-3 text-xs text-muted-foreground"
                >
                    Mostrando {{ metricas.proyectosInconclusos.items.length }} de
                    {{ metricas.proyectosInconclusos.total }} proyectos inconclusos.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
