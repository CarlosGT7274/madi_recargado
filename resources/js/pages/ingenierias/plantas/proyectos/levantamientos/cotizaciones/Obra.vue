<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsCotizaciones, type LevantamientoRef, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
}

export default pageLayout(() => {
    const { planta, proyecto, levantamiento } = usePage<Props>().props;
    return breadcrumbsCotizaciones(planta, proyecto, levantamiento);
});
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CheckCircle2, Clock, Download, FileText, ShieldCheck, Upload } from '@lucide/vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string; folio: string }
interface LevantamientoRef { id: number; folio: string }

interface VersionCotizacion {
    id: number;
    folio: string;
    fecha: string | null;
    total: number | null;
    estado: string;
    tienePartidas: boolean;
    tieneInsumos: boolean;
    archivoExcelUrl: string | null;
}

interface Grupo {
    obra: string;
    aprobada: boolean;
    montoAprobado: number | null;
    totalVersiones: number;
    versiones: VersionCotizacion[];
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
    grupo: Grupo;
}>();

const { hasPermission, Accion } = usePermissions();
const endpoint = 'ingenierias.plantas.proyectos.levantamientos.cotizaciones';
const puedeCrear = hasPermission(endpoint, Accion.CREATE);

const rutaCotizaciones = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
}));

const archivoInput = ref<HTMLInputElement | null>(null);

function subirNuevaVersion(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    // El backend agrupa por nombre de obra automáticamente: al traer el
    // mismo nombre en el Excel, esta versión cae en el mismo grupo.
    router.post(
        CotizacionController.store(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true },
    );
}

const estadoLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const estadoGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    borrador: 'pendiente',
    enviada: 'pendiente',
    aprobada: 'aprobado',
    rechazada: 'negativo',
};

function estadoBadgeClass(estado: string): string {
    const g = estadoGrupo[estado] ?? 'pendiente';
    if (g === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (g === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>

    <Head :title="`${grupo.obra} — Cotizaciones`" />

    <PageLayout :title="grupo.obra" description="Versiones de cotización para esta obra">
        <!-- Banner de estado, igual patrón que Proyecto Completado -->
        <div v-if="grupo.aprobada"
            class="mb-6 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30">
            <div class="flex items-start gap-3 p-5">
                <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-600" />
                <div class="flex-1">
                    <p class="font-semibold text-emerald-800 dark:text-emerald-300">Obra Aprobada</p>
                    <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                        Esta obra tiene una cotización aprobada.
                    </p>
                    <div class="mt-3 rounded-lg bg-white/60 px-4 py-2 text-sm dark:bg-black/20">
                        Monto aprobado: <span class="font-semibold">{{ formatoMoneda(grupo.montoAprobado) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header identidad + stats, estilo azul de Proyecto -->
        <div class="mb-6 overflow-hidden rounded-2xl border shadow-sm"
            :class="grupo.aprobada ? 'border-blue-200' : 'border-border'">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 text-white"
                :class="grupo.aprobada ? 'bg-gradient-to-r from-blue-600 to-blue-500' : 'bg-muted/30 !text-foreground'">
                <div class="flex items-center gap-3">
                    <p class="text-lg font-semibold leading-tight">{{ grupo.obra }}</p>
                    <span v-if="grupo.aprobada"
                        class="flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-medium uppercase">
                        <ShieldCheck class="size-3" /> Completado
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 divide-x divide-y border-t bg-card sm:grid-cols-3 sm:divide-y-0">
                <div class="flex flex-col gap-1 p-4">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <FileText class="size-3.5" /> Versiones
                    </span>
                    <span class="text-xl font-bold">{{ grupo.totalVersiones }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Monto Aprobado</span>
                    <span class="text-xl font-bold">{{ formatoMoneda(grupo.montoAprobado) }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Estado</span>
                    <span class="text-xl font-bold" :class="grupo.aprobada ? 'text-emerald-600' : ''">
                        {{ grupo.aprobada ? 'Aprobado' : 'En proceso' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Versiones: cards planas, sin collapse -->
        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="font-semibold">Versiones de la Obra</p>
                <label v-if="puedeCrear" class="cursor-pointer">
                    <Button size="sm" as="span">
                        <Upload class="mr-2 size-4" />
                        Nueva versión
                    </Button>
                    <input ref="archivoInput" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="subirNuevaVersion" />
                </label>
            </div>

            <div class="space-y-3">
                <div v-for="version in grupo.versiones" :key="version.id"
                    class="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="version.estado === 'aprobada' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-primary/10 text-primary'">
                            <FileText class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ version.folio }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                    :class="estadoBadgeClass(version.estado)">
                                    {{ estadoLabel[version.estado] ?? version.estado }}
                                </span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span>{{ version.fecha ?? '—' }}</span>
                                <span class="font-medium text-foreground">{{ formatoMoneda(version.total) }}</span>
                                <span>{{ version.tienePartidas ? 'Con partidas' : 'Sin partidas' }} · {{
                                    version.tieneInsumos ? 'Con insumos' : 'Sin insumos' }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <a v-if="version.archivoExcelUrl" :href="version.archivoExcelUrl" target="_blank">
                            <Button variant="outline" size="sm">
                                <Download class="mr-1.5 size-3.5" />
                                Excel
                            </Button>
                        </a>
                        <Link :href="CotizacionController.show({
                            planta: planta.id, proyecto: proyecto.id,
                            levantamiento: levantamiento.id, cotizacion: version.id,
                        })">
                            <Button size="sm">Ver Detalle</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
