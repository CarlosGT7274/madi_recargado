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
import {
    Clock,
    Download,
    FileText,
    Layers,
    ShieldCheck,
    Upload,
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
    estatus_admin: string;
    creado: string | null;
}

interface VersionCotizacion {
    id: number;
    folio: string;
    fecha: string | null;
    total: number | null;
    estado: string;
}

interface ObraAgrupada {
    obra: string;
    totalVersiones: number;
    aprobada: boolean;
    ultimaVersion: VersionCotizacion;
}

interface Resumen {
    totalCotizaciones: number;
    totalObras: number;
    totalAprobadas: number;
    montoTotalAprobado: number;
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
        description="Una card por obra; cada Excel subido para la misma obra se guarda como una nueva versión">
        <div class="mb-6 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="grid grid-cols-2 divide-x divide-y sm:grid-cols-4 sm:divide-y-0">
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
            </div>
        </div>

        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Obras Cotizadas</p>
                    <p class="text-sm text-muted-foreground">Cada card representa una obra; entra para ver sus versiones
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="urlPlantilla">
                        <Button variant="outline" size="sm">
                            <Download class="mr-2 size-4" />
                            Descargar Plantilla
                        </Button>
                    </a>
                    <Button v-if="puedeCrear" size="sm" @click="uploadDialogOpen = true">
                        <Upload class="mr-2 size-4" />
                        Subir Cotización
                    </Button>
                </div>
            </div>

            <Dialog v-model:open="uploadDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nueva cotización</DialogTitle>
                        <DialogDescription>
                            Sube el Excel completo. Si el nombre de obra coincide con una cotización existente, se
                            agrega como una nueva versión de esa misma obra.
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

            <div v-if="obras.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="grupo in obras" :key="grupo.obra" :href="CotizacionController.obra({
                    planta: planta.id, proyecto: proyecto.id,
                    levantamiento: levantamiento.id, obra: grupo.obra,
                })" class="flex items-start gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-accent/50">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                        :class="grupo.aprobada ? 'bg-emerald-500/10 text-emerald-600' : 'bg-primary/10 text-primary'">
                        <ShieldCheck v-if="grupo.aprobada" class="size-4" />
                        <Layers v-else class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-semibold">{{ grupo.obra }}</p>
                            <span v-if="grupo.totalVersiones > 1"
                                class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">
                                {{ grupo.totalVersiones }} versiones
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                :class="estadoBadgeClass(grupo.ultimaVersion.estado)">
                                {{ estadoLabel[grupo.ultimaVersion.estado] ?? grupo.ultimaVersion.estado }}
                            </span>
                        </div>
                        <p class="mt-1 truncate text-sm text-muted-foreground">
                            Última versión: {{ grupo.ultimaVersion.folio }} · {{ grupo.ultimaVersion.fecha ?? '—' }}
                        </p>
                        <p class="mt-1 text-sm font-medium">{{ formatoMoneda(grupo.ultimaVersion.total) }}</p>
                    </div>
                </Link>
            </div>

            <p v-else class="rounded-2xl border bg-card py-10 text-center text-sm text-muted-foreground">
                Aún no hay cotizaciones para este levantamiento.
            </p>
        </div>
    </PageLayout>
</template>
