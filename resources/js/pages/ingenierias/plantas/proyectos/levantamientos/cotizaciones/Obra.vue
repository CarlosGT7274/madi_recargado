<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsObra, type LevantamientoRef, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
    grupo: { obra: string };
}

export default pageLayout(() => {
    const { planta, proyecto, levantamiento, grupo } = usePage<Props>().props;
    return breadcrumbsObra(planta, proyecto, levantamiento, grupo.obra);
});
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CheckCircle2, Download, FileText, Upload } from '@lucide/vue';
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
    completada: boolean;
    tienePartidas: boolean;
    tieneInsumos: boolean;
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

    router.post(
        CotizacionController.store(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true },
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

    <PageLayout :title="grupo.obra"
        :description="`${grupo.totalVersiones} ${grupo.totalVersiones === 1 ? 'versión' : 'versiones'}`">
        <div v-if="grupo.completada"
            class="mb-6 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30">
            <div class="flex items-start gap-3 p-5">
                <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-600" />
                <div class="flex-1">
                    <p class="font-semibold text-emerald-800 dark:text-emerald-300">Obra completada</p>
                    <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                        Al menos una versión tiene insumos y orden de compra completos.
                    </p>
                    <div class="mt-3 rounded-lg bg-white/60 px-4 py-2 text-sm dark:bg-black/20">
                        Monto: <span class="font-semibold">{{ formatoMoneda(grupo.montoCompletado) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="font-semibold">Versiones</p>
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
