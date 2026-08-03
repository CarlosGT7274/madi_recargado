<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsLevantamiento, type LevantamientoRef, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';
import GaleriaImagenes from '@/components/GaleriaImagenes.vue';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
}

export default pageLayout(() => {
    const { planta, proyecto, levantamiento } = usePage<Props>().props;
    return breadcrumbsLevantamiento(planta, proyecto, levantamiento);
});
</script>

<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from '@inertiajs/vue3';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import { ArrowLeft, FileSpreadsheet, FileText, Plus, Download, Upload } from '@lucide/vue';
import { ref } from 'vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PartidaController from '@/actions/App/Http/Controllers/Ingenierias/Cotizaciones/PartidaController';
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

import LevantamientoForm from './components/LevantamientoForm.vue';
import type { LevantamientoFormData } from './types';


interface PlantaResumen {
    id: number;
    nombre: string;
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

const props = defineProps<{
    planta: PlantaResumen;
    proyecto: { id: number; nombre: string; folio: string };
    levantamiento: LevantamientoFormData & { id: number; folio: string; nombre: string };
    cotizaciones?: CotizacionResumen[];
}>();

const modo = ref<'view' | 'edit'>('view');
const form = useForm<LevantamientoFormData>({ ...props.levantamiento });
const createCotizacionDialogOpen = ref(false);
const archivoCotizacionInput = ref<HTMLInputElement | null>(null);

function subirCotizacionExcel(): void {
    const archivo = archivoCotizacionInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.store({ planta: props.planta.id, proyecto: props.proyecto.id, levantamiento: props.levantamiento.id }).url,
        { archivo },
        { forceFormData: true, onSuccess: () => (createCotizacionDialogOpen.value = false) },
    );
}

function actualizar(payload: LevantamientoFormData): void {
    Object.assign(form, payload);
}

function guardar(): void {
    form.put(
        LevantamientoController.update({ planta: props.planta.id, proyecto: props.proyecto.id, levantamiento: props.levantamiento.id }).url,
        { onSuccess: () => (modo.value = 'view') },
    );
}

function eliminar(): void {
    if (!confirm(`¿Eliminar el levantamiento "${props.levantamiento.folio}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(
        LevantamientoController.destroy({ planta: props.planta.id, proyecto: props.proyecto.id, levantamiento: props.levantamiento.id }).url,
        { onSuccess: () => router.visit(ProyectoController.show([props.planta.id, props.proyecto.id]).url) },
    );
}

const estadoCotizacionLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const estadoCotizacionGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    borrador: 'pendiente',
    enviada: 'pendiente',
    aprobada: 'aprobado',
    rechazada: 'negativo',
};

function estadoCotizacionBadgeClass(estado: string): string {
    const grupo = estadoCotizacionGrupo[estado] ?? 'pendiente';
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

    <Head :title="`Levantamiento ${levantamiento.folio}`" />

    <PageLayout :title="levantamiento.folio" :description="levantamiento.nombre ?? undefined"
        endpoint="ingenierias.plantas.levantamientos" :with-edit="modo === 'view'" with-delete @edit="modo = 'edit'"
        @delete="eliminar">
        <template #breadcrumbs>
            <Link :href="ProyectoController.show([planta.id, proyecto.id])"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <Dialog v-model:open="createCotizacionDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nueva cotización</DialogTitle>
                    <DialogDescription>
                        Sube la plantilla de cotización llena (descárgala primero con el botón "Descargar Plantilla").
                    </DialogDescription>
                </DialogHeader>

                <label
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed py-10 text-sm font-medium text-muted-foreground hover:bg-accent">
                    <Upload class="size-5" />
                    <span class="text-primary">Seleccionar Excel</span>
                    <span class="text-xs">Formatos: .xlsx, .xls</span>
                    <input ref="archivoCotizacionInput" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="subirCotizacionExcel" />
                </label>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <LevantamientoForm :mode="modo" :data="form" :errors="form.errors" @update="actualizar" />

        <GaleriaImagenes class="mt-6" archivable-type="levantamiento" :archivable-id="levantamiento.id"
            :imagenes="levantamiento.imagenes" :solo-lectura="modo === 'view'" />

        <div v-if="modo === 'edit'" class="mt-6 flex justify-end gap-2">
            <Button variant="secondary" @click="modo = 'view'">Cancelar</Button>
            <Button :disabled="form.processing" @click="guardar">Guardar cambios</Button>
        </div>

        <!-- Cotizaciones (recuperado del Show.vue original) -->
        <div v-if="modo === 'view'" class="mt-6 rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                    <FileSpreadsheet class="size-4" />
                    Cotizaciones
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="PartidaController.plantillaGenerica({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id }).url">
                        <Button variant="outline" size="sm">
                            <Download class="mr-2 size-4" />
                            Descargar Plantilla
                        </Button>
                    </a>
                    <Button size="sm" @click="createCotizacionDialogOpen = true">
                        <Plus class="mr-2 size-4" />
                        Crear cotización
                    </Button>
                </div>
            </div>

            <Deferred data="cotizaciones">
                <template #fallback>
                    <p class="py-6 text-center text-sm text-muted-foreground">Cargando cotizaciones…</p>
                </template>

                <div v-if="cotizaciones?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="cot in cotizaciones" :key="cot.id" :href="CotizacionController.show({
                        planta: planta.id,
                        proyecto: proyecto.id,
                        levantamiento: levantamiento.id,
                        cotizacion: cot.id,
                    })
                        "
                        class="flex items-start gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-accent/50">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <FileText class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate font-semibold">{{ cot.folio }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                    :class="estadoCotizacionBadgeClass(cot.estado)">
                                    {{ estadoCotizacionLabel[cot.estado] ?? cot.estado }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-sm text-muted-foreground">{{ cot.cliente ?? '—' }}</p>
                            <p class="mt-1 text-sm font-medium">{{ formatoMoneda(cot.total) }}</p>
                        </div>
                    </Link>
                </div>

                <p v-else class="py-6 text-center text-sm text-muted-foreground">
                    Aún no hay cotizaciones para este levantamiento.
                </p>
            </Deferred>
        </div>
    </PageLayout>
</template>
