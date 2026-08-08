<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsLevantamiento, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
}

export default pageLayout(() => {
    const { planta, proyecto } = usePage<Props>().props;
    return breadcrumbsLevantamiento(planta, proyecto, { id: usePage<{ levantamiento: { id: number; folio: string } }>().props.levantamiento.id, folio: usePage<{ levantamiento: { id: number; folio: string } }>().props.levantamiento.folio });
});
</script>

<script setup lang="ts">
import { Deferred, Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileDown, FileSpreadsheet, Layers, Plus, ShieldCheck, ShoppingCart, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PageLayout from '@/components/PageLayout.vue';
import GaleriaImagenes from '@/components/GaleriaImagenes.vue';

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

interface UltimaVersion {
    id: number;
    folio: string;
    fecha: string | null;
    total: number | null;
    completada: boolean;
    tieneInsumos: boolean;
    tieneAutorizacion: boolean;
}

interface ObraAgrupada {
    obra: string;
    totalVersiones: number;
    completada: boolean;
    ultimaVersion: UltimaVersion;
}

const props = defineProps<{
    planta: PlantaResumen;
    proyecto: { id: number; nombre: string; folio: string };
    levantamiento: LevantamientoFormData & { id: number; folio: string; nombre: string };
    obras?: ObraAgrupada[];
}>();

const modo = ref<'view' | 'edit'>('view');
const form = useForm<LevantamientoFormData>({ ...props.levantamiento });
const createCotizacionDialogOpen = ref(false);
const archivoCotizacionInput = ref<HTMLInputElement | null>(null);

const rutaCotizaciones = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
}));

const urlPdf = computed(() =>
    LevantamientoController.pdf(rutaCotizaciones.value).url,
);

function subirCotizacionExcel(): void {
    const archivo = archivoCotizacionInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.store(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, onSuccess: () => (createCotizacionDialogOpen.value = false) },
    );
}

function actualizar(payload: LevantamientoFormData): void {
    Object.assign(form, payload);
}

function guardar(): void {
    form.put(
        LevantamientoController.update(rutaCotizaciones.value).url,
        { onSuccess: () => (modo.value = 'view') },
    );
}

function eliminar(): void {
    if (!confirm(`¿Eliminar el levantamiento "${props.levantamiento.folio}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(LevantamientoController.destroy(rutaCotizaciones.value).url);
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

        <template #actions>
            <a :href="urlPdf" target="_blank" rel="noopener noreferrer">
                <Button variant="outline">
                    <FileDown class="mr-2 size-4" />
                    Exportar PDF
                </Button>
            </a>
        </template>

        <Dialog v-model:open="createCotizacionDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nueva cotización</DialogTitle>
                    <DialogDescription>
                        Sube el Excel. Si el nombre de obra coincide con una existente, se agrega como nueva versión.
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

        <!-- Obras: única lista de agrupación de todo el flujo -->
        <div v-if="modo === 'view'" class="mt-6 rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                    <FileSpreadsheet class="size-4" />
                    Cotizaciones
                </div>
                <Button size="sm" @click="createCotizacionDialogOpen = true">
                    <Plus class="mr-2 size-4" />
                    Subir cotización
                </Button>
            </div>

            <Deferred data="obras">
                <template #fallback>
                    <p class="py-6 text-center text-sm text-muted-foreground">Cargando cotizaciones…</p>
                </template>

                <div v-if="obras?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="grupo in obras" :key="grupo.obra" :href="CotizacionController.obra({
                        planta: planta.id,
                        proyecto: proyecto.id,
                        levantamiento: levantamiento.id,
                        obra: grupo.obra,
                    })" class="flex flex-col gap-3 rounded-xl border p-4 transition-colors hover:bg-accent/50"
                        :class="grupo.completada ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'bg-card'">
                        <div class="flex items-start justify-between gap-2">
                            <p class="truncate font-semibold">{{ grupo.obra }}</p>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                :class="grupo.completada ? 'bg-emerald-500/15 text-emerald-600' : 'bg-muted text-muted-foreground'">
                                {{ grupo.completada ? 'Completado' : 'En proceso' }}
                            </span>
                        </div>

                        <div v-if="grupo.ultimaVersion.tieneAutorizacion"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <ShoppingCart class="size-3.5" />
                            Con orden de compra
                        </div>

                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ formatoMoneda(grupo.ultimaVersion.total) }}</span>
                            <span>{{ grupo.totalVersiones }} {{ grupo.totalVersiones === 1 ? 'versión' : 'versiones'
                                }}</span>
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
