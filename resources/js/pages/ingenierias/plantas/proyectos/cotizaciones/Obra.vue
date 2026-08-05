<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsObraDirecto, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    grupo: { obra: string };
}

export default pageLayout(() => {
    const { planta, proyecto, grupo } = usePage<Props>().props;
    return breadcrumbsObraDirecto(planta, proyecto, grupo.obra);
});
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CheckCircle2, Download, FileText, ShoppingCart, Upload } from '@lucide/vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string; folio: string }

interface VersionCotizacion {
    id: number;
    folio: string;
    fecha: string | null;
    total: number | null;
    estado: string;
    completada: boolean;
    tienePartidas: boolean;
    tieneOrdenAprobada: boolean;
    archivoExcelUrl: string | null;
}

interface Grupo {
    obra: string;
    completada: boolean;
    montoCompletado: number | null;
    totalVersiones: number;
    versiones: VersionCotizacion[];
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    grupo: Grupo;
}>();

const { hasPermission, Accion } = usePermissions();
const endpoint = 'ingenierias.plantas.proyectos.cotizaciones';
const puedeCrear = hasPermission(endpoint, Accion.CREATE);

const rutaCotizaciones = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
}));

const versionesAprobadas = computed(
    () => props.grupo.versiones.filter((v) => v.completada).length,
);

const archivoInput = ref<HTMLInputElement | null>(null);

function subirNuevaVersion(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.storeProyecto(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true, onFinish: () => { if (archivoInput.value) archivoInput.value.value = ''; } },
    );
}

const estadoLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    rechazada: 'Rechazada',
};

function badgeClase(version: VersionCotizacion): string {
    if (version.completada) return 'bg-emerald-500/10 text-emerald-600';
    if (version.estado === 'rechazada') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function badgeTexto(version: VersionCotizacion): string {
    if (version.completada) return 'Completada';
    return estadoLabel[version.estado] ?? version.estado;
}

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>

    <Head :title="`${grupo.obra} — Cotizaciones`" />

    <PageLayout title="" description="">
        <div v-if="grupo.completada"
            class="mb-6 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/30">
            <div class="flex items-start gap-3 p-5">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white">
                    <CheckCircle2 class="size-5" />
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-blue-800 dark:text-blue-300">Obra completada</p>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                        Al menos una versión tiene orden de compra.
                    </p>
                    <div class="mt-3 rounded-lg bg-white/60 px-4 py-2 text-sm dark:bg-black/20">
                        Monto total aprobado: <span class="font-semibold">{{ formatoMoneda(grupo.montoCompletado)
                            }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border shadow-sm">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6 text-white">
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-2xl font-bold">{{ grupo.obra }}</p>
                    <span class="rounded-full px-3 py-1 text-xs font-bold uppercase"
                        :class="grupo.completada ? 'bg-emerald-500' : 'bg-white/20'">
                        {{ grupo.completada ? 'Completado' : 'En proceso' }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-blue-100">
                    {{ grupo.totalVersiones }} {{ grupo.totalVersiones === 1 ? 'versión' : 'versiones' }}
                </p>
            </div>

            <div class="grid grid-cols-1 divide-y border-b bg-card sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="flex items-center gap-3 p-5">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600">
                        <FileText class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Cotizaciones Totales</p>
                        <p class="text-xl font-bold">{{ grupo.totalVersiones }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-5">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                        <CheckCircle2 class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Cotizaciones Aprobadas</p>
                        <p class="text-xl font-bold">{{ versionesAprobadas }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ grupo.totalVersiones ? Math.round((versionesAprobadas / grupo.totalVersiones) * 100) : 0
                            }}% del total
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-5">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600">
                        <ShoppingCart class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Monto Total Aprobado</p>
                        <p class="text-xl font-bold">{{ formatoMoneda(grupo.montoCompletado) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-lg font-semibold">Versiones de esta Obra</p>
                    <p class="text-sm text-muted-foreground">Gestiona y revisa todas las cotizaciones</p>
                </div>

                <label v-if="puedeCrear">
                    <Button size="sm" as="span" class="cursor-pointer">
                        <Upload class="mr-2 size-4" />
                        Nueva versión
                    </Button>
                    <input ref="archivoInput" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="subirNuevaVersion" />
                </label>
            </div>

            <div class="space-y-3">
                <div v-for="version in grupo.versiones" :key="version.id"
                    class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                    :class="version.completada ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'bg-card'">
                    <div class="flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="version.completada ? 'bg-emerald-500/10 text-emerald-600' : 'bg-primary/10 text-primary'">
                            <FileText class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ version.folio }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                    :class="badgeClase(version)">
                                    {{ badgeTexto(version) }}
                                </span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span>{{ version.fecha ?? '—' }}</span>
                                <span class="font-medium text-foreground">{{ formatoMoneda(version.total) }}</span>
                                <span>{{ version.tienePartidas ? 'Con partidas' : 'Sin partidas' }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <a v-if="version.archivoExcelUrl" :href="version.archivoExcelUrl" target="_blank">
                            <Button variant="outline" size="sm">
                                <Download class="mr-1.5 size-3.5" />
                                Descargar Excel
                            </Button>
                        </a>

                        <Link :href="CotizacionController.showProyecto({
                            planta: planta.id, proyecto: proyecto.id, cotizacion: version.id,
                        })">
                            <Button size="sm" class="bg-violet-600 text-white hover:bg-violet-700">Ver Detalle</Button>
                        </Link>
                    </div>
                </div>

                <p v-if="!grupo.versiones.length"
                    class="rounded-xl border py-8 text-center text-sm text-muted-foreground">
                    Aún no hay versiones para esta obra.
                </p>
            </div>
        </div>
    </PageLayout>
</template>
