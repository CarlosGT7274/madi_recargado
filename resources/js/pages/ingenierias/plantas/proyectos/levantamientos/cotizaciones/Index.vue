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
import { computed, reactive, ref } from 'vue';
import {
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Clock,
    Download,
    FileText,
    Layers,
    ShieldCheck,
    Upload,
    XCircle,
} from '@lucide/vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
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
import { usePermissions } from '@/composables/usePermissions';

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

interface VersionCotizacion {
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

interface ObraAgrupada {
    obra: string;
    totalVersiones: number;
    ultimaVersion: VersionCotizacion;
    versiones: VersionCotizacion[];
}

interface Resumen {
    totalCotizaciones: number;
    totalObras: number;
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
    obras: ObraAgrupada[];
}>();

const { hasPermission, Accion } = usePermissions();
const endpoint = 'ingenierias.plantas.proyectos.levantamientos.cotizaciones';
const puedeCrear = hasPermission(endpoint, Accion.CREATE);

const rutaCotizaciones = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
}));

const urlPlantilla = computed(() => CotizacionController.plantilla(rutaCotizaciones.value).url);

// --- Estado del levantamiento (banner) ---
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

const esFinal = ['ganada', 'perdida', 'cancelada'].includes(props.levantamiento.estatus_admin);
const esGanada = props.levantamiento.estatus_admin === 'ganada';

// --- Subir cotización nueva (siempre crea una versión nueva) ---
const uploadDialogOpen = ref(false);
const archivoInput = ref<HTMLInputElement | null>(null);

function subirCotizacionExcel(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.store(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true, onSuccess: () => (uploadDialogOpen.value = false) },
    );
}

// --- Subir nueva versión sobre una obra ya existente (mismo flujo, misma obra) ---
const inputsNuevaVersion = reactive<Record<string, HTMLInputElement | null>>({});

function subirNuevaVersion(obra: string): void {
    const archivo = inputsNuevaVersion[obra]?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.store(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true },
    );
}

// --- Expandir/colapsar grupos de obra ---
const obrasAbiertas = reactive<Record<string, boolean>>({});

function toggleObra(obra: string): void {
    obrasAbiertas[obra] = !obrasAbiertas[obra];
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

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>

    <Head title="Cotizaciones" />

    <PageLayout :title="`Cotizaciones — ${levantamiento.folio}`"
        description="Cotizaciones agrupadas por obra; cada Excel subido para la misma obra se guarda como una nueva versión">
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

        <!-- Header: identidad + stats -->
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

            <div class="grid grid-cols-2 divide-x divide-y border-b sm:grid-cols-5 sm:divide-y-0">
                <div class="flex flex-col gap-1 p-4">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Layers class="size-3.5" /> Obras
                    </span>
                    <span class="text-xl font-bold">{{ resumen.totalObras }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <FileText class="size-3.5" /> Versiones Totales
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
                    <span class="text-xs text-muted-foreground">Monto Aprobado</span>
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
        </div>

        <!-- Lista de obras agrupadas -->
        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Obras Cotizadas</p>
                    <p class="text-sm text-muted-foreground">Cada obra agrupa todas sus versiones de cotización</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="urlPlantilla">
                        <Button variant="outline" size="sm">
                            <Download class="mr-2 size-4" />
                            Descargar Plantilla
                        </Button>
                    </a>
                    <Button v-if="!esFinal && puedeCrear" size="sm" @click="uploadDialogOpen = true">
                        <Upload class="mr-2 size-4" />
                        Subir Cotización
                    </Button>
                    <span v-else-if="esFinal"
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
                            Sube el Excel completo (encabezado + partidas). Si el nombre de la obra coincide con una
                            cotización existente, se agrega como una nueva versión de esa misma obra.
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
                <div v-for="grupo in obras" :key="grupo.obra" class="overflow-hidden rounded-xl border bg-card">
                    <!-- Fila de la obra (siempre visible = última versión) -->
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" class="flex flex-1 items-start gap-3 text-left"
                            @click="toggleObra(grupo.obra)">
                            <component
                                :is="grupo.totalVersiones > 1 ? (obrasAbiertas[grupo.obra] ? ChevronDown : ChevronRight) : FileText"
                                class="mt-1 size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ grupo.obra }}</p>
                                    <span v-if="grupo.totalVersiones > 1"
                                        class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">
                                        {{ grupo.totalVersiones }} versiones
                                    </span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                        :class="estadoBadgeClass(grupo.ultimaVersion.estado)">
                                        {{ estadoLabel[grupo.ultimaVersion.estado] ?? grupo.ultimaVersion.estado }}
                                    </span>
                                </div>
                                <p
                                    class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span>Última versión: {{ grupo.ultimaVersion.folio }}</span>
                                    <span>{{ grupo.ultimaVersion.fecha ?? '—' }}</span>
                                    <span class="font-medium text-foreground">{{
                                        formatoMoneda(grupo.ultimaVersion.total) }}</span>
                                </p>
                            </div>
                        </button>

                        <div class="flex shrink-0 items-center gap-2">
                            <label v-if="!esFinal && puedeCrear" class="cursor-pointer">
                                <Button variant="outline" size="sm" as="span">
                                    <Upload class="mr-1.5 size-3.5" />
                                    Nueva versión
                                </Button>
                                <input :ref="(el) => (inputsNuevaVersion[grupo.obra] = el as HTMLInputElement)"
                                    type="file" accept=".xlsx,.xls" class="hidden"
                                    @change="subirNuevaVersion(grupo.obra)" />
                            </label>
                            <Link :href="CotizacionController.show({
                                planta: planta.id, proyecto: proyecto.id,
                                levantamiento: levantamiento.id, cotizacion: grupo.ultimaVersion.id,
                            })">
                                <Button size="sm">Ver Última Versión</Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Versiones anteriores (colapsable) -->
                    <div v-if="obrasAbiertas[grupo.obra] && grupo.totalVersiones > 1"
                        class="divide-y border-t bg-muted/20">
                        <Link v-for="version in grupo.versiones" :key="version.id" :href="CotizacionController.show({
                            planta: planta.id, proyecto: proyecto.id,
                            levantamiento: levantamiento.id, cotizacion: version.id,
                        })"
                            class="flex items-center justify-between gap-3 px-4 py-2.5 pl-11 text-sm hover:bg-accent/50">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="font-medium">{{ version.folio }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                    :class="estadoBadgeClass(version.estado)">
                                    {{ estadoLabel[version.estado] ?? version.estado }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ version.fecha ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium">{{ formatoMoneda(version.total) }}</span>
                                <a v-if="version.archivoExcelUrl" :href="version.archivoExcelUrl" target="_blank"
                                    @click.stop>
                                    <Download class="size-3.5 text-muted-foreground hover:text-foreground" />
                                </a>
                            </div>
                        </Link>
                    </div>
                </div>

                <p v-if="!obras.length"
                    class="rounded-2xl border bg-card py-10 text-center text-sm text-muted-foreground">
                    Aún no hay cotizaciones para este levantamiento.
                </p>
            </div>
        </div>
    </PageLayout>
</template>
