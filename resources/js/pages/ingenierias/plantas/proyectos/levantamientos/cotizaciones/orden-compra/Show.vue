<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, FileWarning, ShoppingCart, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import ArchivoController from '@/actions/App/Http/Controllers/ArchivoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string }
interface LevantamientoRef { id: number; folio: string }

interface CotizacionInfo {
    id: number;
    folio: string;
    obra: string | null;
    total: number;
    tieneInsumos: boolean;
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
    cotizacion: CotizacionInfo;
    archivoId: number | null;
    pdfUrl: string | null;
    pdfNombre: string | null;
    subidoEl: string | null;
}>();

const rutaOc = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
    cotizacion: props.cotizacion.id,
}));

const archivoInput = ref<HTMLInputElement | null>(null);

function subirArchivo(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.subirAutorizacion(rutaOc.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true },
    );
}

function eliminarPdf(archivoId: number): void {
    router.delete(ArchivoController.destroy(archivoId).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Orden de Compra" />

    <PageLayout title="" description="">
        <div class="overflow-hidden rounded-2xl border shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-6 text-white">
                <div class="flex items-center gap-3">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15">
                        <ShoppingCart class="size-6" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">Orden de Compra</p>
                        <p class="text-sm text-blue-100">{{ cotizacion.folio }}</p>
                    </div>
                </div>

                <span v-if="pdfUrl" class="flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold uppercase">
                    <CheckCircle2 class="size-3.5" />
                    Cumplido
                </span>
                <span v-else class="flex items-center gap-1.5 rounded-full bg-amber-500 px-3 py-1 text-xs font-bold uppercase">
                    <FileWarning class="size-3.5" />
                    Pendiente
                </span>
            </div>
        </div>

        <div v-if="!cotizacion.tieneInsumos"
            class="mt-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30">
            <FileWarning class="mt-0.5 size-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-semibold text-amber-800 dark:text-amber-300">Explosión de Insumos pendiente</p>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    Debes completar la Explosión de Insumos antes de poder subir la Orden de Compra.
                </p>
            </div>
        </div>

        <div v-else-if="pdfUrl" class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">{{ pdfNombre }}</p>
                    <p class="text-xs text-muted-foreground">Subido el {{ subidoEl ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="pdfUrl" target="_blank">
                        <Button variant="outline" size="sm">Abrir en pestaña nueva</Button>
                    </a>
                    <Button variant="outline" size="sm" class="text-destructive hover:bg-destructive/10"
                        @click="archivoId && eliminarPdf(archivoId)">
                        Quitar y reemplazar
                    </Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-muted/20">
                <iframe :src="pdfUrl" class="h-[75vh] w-full" title="Orden de Compra" />
            </div>
        </div>

        <div v-else class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <p class="mb-4 font-semibold">Sube el PDF de la Orden de Compra</p>

            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed py-16 text-sm font-medium text-muted-foreground hover:bg-accent">
                <Upload class="size-6" />
                <span class="text-primary">Seleccionar PDF</span>
                <span class="text-xs">Con solo subirlo, este requisito queda cumplido</span>
                <input ref="archivoInput" type="file" accept="application/pdf" class="hidden" @change="subirArchivo" />
            </label>
        </div>
    </PageLayout>
</template>
