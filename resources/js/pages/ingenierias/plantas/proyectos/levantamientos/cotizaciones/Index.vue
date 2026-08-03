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
import { ref } from 'vue';
import { CheckCircle2, Clock, FileText, Upload } from '@lucide/vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface PlantaRef {
    id: number;
    nombre: string;
}

interface ProyectoRef {
    id: number;
    nombre: string;
    folio: string;
}

interface LevantamientoRef {
    id: number;
    folio: string;
}

interface CotizacionResumen {
    id: number;
    folio: string;
    fecha: string | null;
    cliente: string | null;
    vendedor: string | null;
    total: number | null;
    estado: string;
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
    levantamiento: LevantamientoRef;
    resumen: Resumen;
    cotizaciones: CotizacionResumen[];
}>();

const uploadDialogOpen = ref(false);
const archivoInput = ref<HTMLInputElement | null>(null);

function subirCotizacionExcel(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.store({ planta: props.planta.id, proyecto: props.proyecto.id, levantamiento: props.levantamiento.id }).url,
        { archivo },
        { forceFormData: true, onSuccess: () => (uploadDialogOpen.value = false) },
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
        description="Administra todas las cotizaciones de este levantamiento">
        <template #actions>
            <Button @click="uploadDialogOpen = true">
                <Upload class="mr-2 size-4" />
                Subir Cotización
            </Button>
        </template>

        <Dialog v-model:open="uploadDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nueva cotización</DialogTitle>
                    <DialogDescription>Sube el Excel de la cotización para crearla.</DialogDescription>
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

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <FileText class="size-4" />
                    Cotizaciones Totales
                </div>
                <p class="mt-2 text-2xl font-bold">{{ resumen.totalCotizaciones }}</p>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <CheckCircle2 class="size-4 text-emerald-600" />
                    Aprobadas
                </div>
                <p class="mt-2 text-2xl font-bold text-emerald-600">{{ resumen.totalAprobadas }}</p>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="text-sm text-muted-foreground">Monto Total Aprobado</div>
                <p class="mt-2 text-2xl font-bold">{{ formatoMoneda(resumen.montoTotalAprobado) }}</p>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Clock class="size-4" />
                    Tiempo Restante
                </div>
                <p v-if="resumen.yaEnviada" class="mt-2 text-lg font-bold text-emerald-600">Enviada</p>
                <p v-else-if="resumen.tiempoRestanteHoras === null"
                    class="mt-2 text-lg font-bold text-muted-foreground">Sin
                    fecha</p>
                <p v-else class="mt-2 text-2xl font-bold"
                    :class="resumen.tiempoRestanteHoras < 0 ? 'text-red-600' : ''">
                    {{ resumen.tiempoRestanteHoras < 0 ? 'Vencido ' : '' }}{{ Math.abs(resumen.tiempoRestanteHoras) }}h
                        </p>
            </div>
        </div>

        <div class="mt-6 space-y-3">
            <Link v-for="cot in cotizaciones" :key="cot.id"
                :href="CotizacionController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id, cotizacion: cot.id })"
                class="flex items-center justify-between gap-4 rounded-2xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <FileText class="size-4" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold">{{ cot.folio }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                :class="estadoBadgeClass(cot.estado)">
                                {{ estadoLabel[cot.estado] ?? cot.estado }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">{{ cot.cliente ?? '—' }}</p>
                    </div>
                </div>
                <p class="font-medium">{{ formatoMoneda(cot.total) }}</p>
            </Link>

            <p v-if="!cotizaciones.length"
                class="rounded-2xl border bg-card py-10 text-center text-sm text-muted-foreground">
                Aún no hay cotizaciones para este levantamiento.
            </p>
        </div>
    </PageLayout>
</template>
