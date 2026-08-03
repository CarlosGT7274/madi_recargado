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
import { CheckCircle2, Clock, Download, FileText, ShieldCheck, Upload, XCircle } from '@lucide/vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PartidaController from '@/actions/App/Http/Controllers/Ingenierias/Cotizaciones/PartidaController';
import ArchivoController from '@/actions/App/Http/Controllers/ArchivoController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string; folio: string }

interface LevantamientoDetalle {
    id: number;
    folio: string;
    nombre: string | null;
    cliente: string | null;
    estatus_admin: string;
    creado: string | null;
}

interface CotizacionResumen {
    id: number;
    folio: string;
    fecha: string | null;
    cliente: string | null;
    vendedor: string | null;
    total: number | null;
    estado: string;
    tienePartidas: boolean;
    tieneInsumos: boolean;
    archivoExcelUrl: string | null;
}

interface Resumen {
    totalCotizaciones: number;
    totalAprobadas: number;
    montoTotalAprobado: number;
    tiempoRestanteHoras: number | null;
    yaEnviada: boolean;
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoDetalle;
    resumen: Resumen;
    cotizaciones: CotizacionResumen[];
}>();

// --- Estado del levantamiento (banner + candado) ---
const estatusAdminLabel: Record<string, string> = {
    recibida: 'Recibida',
    levantamiento_proceso: 'En proceso',
    levantamiento_listo: 'Listo',
    cotizando: 'Cotizando',
    revision_residente: 'Revisión',
    correcciones: 'Correcciones',
    lista_enviar: 'Lista enviar',
    enviada: 'Enviada',
    ganada: 'Ganada',
    perdida: 'Perdida',
    cancelada: 'Cancelada',
};

const esFinal = computed(() => ['ganada', 'perdida', 'cancelada'].includes(props.levantamiento.estatus_admin));
const esGanada = computed(() => props.levantamiento.estatus_admin === 'ganada');

// --- Subir cotización nueva (solo guarda, no procesa) ---
const uploadDialogOpen = ref(false);
const archivoInput = ref<HTMLInputElement | null>(null);

function subirCotizacionExcel(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        ArchivoController.storeDocumento().url,
        { archivo, archivable_type: 'levantamiento', archivable_id: props.levantamiento.id },
        { forceFormData: true, preserveScroll: true, onSuccess: () => (uploadDialogOpen.value = false) },
    );
}

// --- Subir nueva versión sobre una cotización existente ---
const inputsVersion = ref<Record<number, HTMLInputElement | null>>({});

function subirVersion(cotizacionId: number): void {
    const archivo = inputsVersion.value[cotizacionId]?.files?.[0];
    if (!archivo) return;

    router.post(
        ArchivoController.storeDocumento().url,
        { archivo, archivable_type: 'cotizacion', archivable_id: cotizacionId },
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
    const grupo = estadoGrupo[estado] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function tagsCotizacion(cot: CotizacionResumen): string {
    return [cot.tienePartidas && 'Con partidas', cot.tieneInsumos && 'Con insumos'].filter(Boolean).join(' • ');
}

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>

    <Head title="Cotizaciones" />

    <PageLayout :title="`Cotizaciones — ${levantamiento.folio}`"
        description="Administra todas las cotizaciones de este levantamiento">
        <!-- Banner de estado final -->
        <div v-if="esFinal" class="mb-6 overflow-hidden rounded-2xl border"
            :class="esGanada ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30' : 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30'">
            <div class="flex items-start gap-3 p-5">
                <component :is="esGanada ? CheckCircle2 : XCircle" class="mt-0.5 size-5 shrink-0"
                    :class="esGanada ? 'text-emerald-600' : 'text-red-600'" />
                <div class="flex-1">
                    <p class="font-semibold"
                        :class="esGanada ? 'text-emerald-800 dark:text-emerald-300' : 'text-red-800 dark:text-red-300'">
                        Levantamiento {{ estatusAdminLabel[levantamiento.estatus_admin] }}
                    </p>
                    <p class="mt-1 text-sm"
                        :class="esGanada ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'">
                        Este levantamiento tiene {{ resumen.totalAprobadas }} cotización(es) aprobada(s).
                        <span v-if="esGanada">El proyecto se considera cerrado.</span>
                    </p>
                    <div v-if="esGanada" class="mt-3 rounded-lg bg-white/60 px-4 py-2 text-sm dark:bg-black/20">
                        Monto total aprobado: <span class="font-semibold">{{ formatoMoneda(resumen.montoTotalAprobado)
                        }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header: identidad + stats + descripción -->
        <div class="mb-6 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/30 px-6 py-5">
                <div class="flex items-center gap-3">
                    <p class="text-lg font-semibold leading-tight">{{ levantamiento.nombre ?? levantamiento.folio }}</p>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-medium uppercase"
                        :class="esFinal ? (esGanada ? 'bg-emerald-500/10 text-emerald-600' : 'bg-red-500/10 text-red-600') : 'bg-amber-500/10 text-amber-600'">
                        {{ estatusAdminLabel[levantamiento.estatus_admin] }}
                    </span>
                </div>
                <p class="text-sm text-muted-foreground">Creado el {{ levantamiento.creado ?? '—' }}</p>
            </div>

            <div class="grid grid-cols-2 divide-x divide-y border-b sm:grid-cols-4 sm:divide-y-0">
                <div class="flex flex-col gap-1 p-4">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <FileText class="size-3.5" /> Cotizaciones Totales
                    </span>
                    <span class="text-xl font-bold">{{ resumen.totalCotizaciones }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <ShieldCheck class="size-3.5 text-emerald-600" /> Aprobadas
                    </span>
                    <span class="text-xl font-bold text-emerald-600">{{ resumen.totalAprobadas }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Monto Total Aprobado</span>
                    <span class="text-xl font-bold">{{ formatoMoneda(resumen.montoTotalAprobado) }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Clock class="size-3.5" /> Tiempo Restante
                    </span>
                    <span v-if="resumen.yaEnviada" class="text-base font-bold text-emerald-600">Enviada</span>
                    <span v-else-if="resumen.tiempoRestanteHoras === null"
                        class="text-base font-bold text-muted-foreground">Sin fecha</span>
                    <span v-else class="text-xl font-bold"
                        :class="resumen.tiempoRestanteHoras < 0 ? 'text-red-600' : ''">
                        {{ resumen.tiempoRestanteHoras < 0 ? 'Vencido ' : '' }}{{ Math.abs(resumen.tiempoRestanteHoras)
                        }}h </span>
                </div>
            </div>

            <div v-if="levantamiento.cliente" class="px-6 py-4">
                <p class="mb-1 text-xs text-muted-foreground">Descripción</p>
                <div class="rounded-lg bg-muted/40 px-4 py-2.5 text-sm">
                    Cliente: {{ levantamiento.cliente }}
                </div>
            </div>
        </div>

        <!-- Lista de cotizaciones -->
        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Cotizaciones del Levantamiento</p>
                    <p class="text-sm text-muted-foreground">Gestiona y revisa todas las cotizaciones</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="PartidaController.plantillaGenerica({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id }).url">
                        <Button variant="outline" size="sm">
                            <Download class="mr-2 size-4" />
                            Descargar Plantilla
                        </Button>
                    </a>
                    <Button v-if="!esFinal" size="sm" @click="uploadDialogOpen = true">
                        <Upload class="mr-2 size-4" />
                        Subir Cotización
                    </Button>
                    <span v-else
                        class="flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs text-muted-foreground">
                        <ShieldCheck class="size-3.5" />
                        Levantamiento {{ estatusAdminLabel[levantamiento.estatus_admin] }}
                    </span>
                </div>
            </div>

            <Dialog v-model:open="uploadDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nueva cotización</DialogTitle>
                        <DialogDescription>
                            Sube el Excel de la cotización. Por ahora solo se almacena; el sistema aún no lo procesa
                            automáticamente.
                        </DialogDescription>
                    </DialogHeader>

                    <label
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed py-10 text-sm font-medium text-muted-foreground hover:bg-accent">
                        <Upload class="size-5" />
                        <span class="text-primary">Seleccionar Excel</span>
                        <span class="text-xs">Formatos: .xlsx, .xls</span>
                        <input ref="archivoInput" type="file" accept=".xlsx,.xls" class="hidden"
                            @change="subirCotizacionExcel" />
                    </label>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div class="space-y-3">
                <div v-for="cot in cotizaciones" :key="cot.id"
                    class="flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <Link
                        :href="CotizacionController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id, cotizacion: cot.id })"
                        class="flex flex-1 items-start gap-3">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <FileText class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ cot.folio }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                    :class="estadoBadgeClass(cot.estado)">
                                    {{ estadoLabel[cot.estado] ?? cot.estado }}
                                </span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span>{{ cot.fecha ?? '—' }}</span>
                                <span class="font-medium text-foreground">{{ formatoMoneda(cot.total) }}</span>
                                <span v-if="cot.tienePartidas || cot.tieneInsumos">{{ tagsCotizacion(cot) }}</span>
                            </p>
                        </div>
                    </Link>

                    <div class="flex shrink-0 items-center gap-2">
                        <label class="cursor-pointer">
                            <Button variant="outline" size="sm" as="span">
                                <Upload class="mr-1.5 size-3.5" />
                                Subir Excel
                            </Button>
                            <input :ref="(el) => (inputsVersion[cot.id] = el as HTMLInputElement)" type="file"
                                accept=".xlsx,.xls" class="hidden" @change="subirVersion(cot.id)" />
                        </label>
                        <a v-if="cot.archivoExcelUrl" :href="cot.archivoExcelUrl" target="_blank">
                            <Button variant="outline" size="sm">
                                <Download class="mr-1.5 size-3.5" />
                                Descargar Excel
                            </Button>
                        </a>
                        <Link
                            :href="CotizacionController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id, cotizacion: cot.id })">
                            <Button size="sm">Ver Detalle</Button>
                        </Link>
                    </div>
                </div>

                <p v-if="!cotizaciones.length"
                    class="rounded-2xl border bg-card py-10 text-center text-sm text-muted-foreground">
                    Aún no hay cotizaciones para este levantamiento.
                </p>
            </div>
        </div>
    </PageLayout>
</template>
