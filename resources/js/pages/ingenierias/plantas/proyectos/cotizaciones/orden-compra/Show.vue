<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    breadcrumbsCotizacionDirecto,
    type CotizacionRef,
    type PlantaRef,
    type ProyectoRef,
    pageLayout,
} from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    cotizacion: CotizacionRef & { obra?: string | number | null };
}

export default pageLayout(() => {
    const { planta, proyecto, cotizacion } =
        usePage<Props>().props;
    return breadcrumbsCotizacionDirecto(planta, proyecto, cotizacion);
});
</script>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    ChevronLeft,
    CircleAlert,
    FileCheck2,
    FileText,
    Save,
    ShoppingCart,
    Upload,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ArchivoController from '@/actions/App/Http/Controllers/ArchivoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';

interface PlantaInfo {
    id: number;
    nombre: string;
}
interface ProyectoInfo {
    id: number;
    nombre: string;
}
type EstadoCompra = 'pendiente' | 'aprobado' | 'rechazado';

interface CotizacionInfo {
    id: number;
    folio: string;
    obra: string | null;
    total: number;
    estatusCompra: EstadoCompra;
    fechaEstatus: string | null;
}

const props = defineProps<{
    planta: PlantaInfo;
    proyecto: ProyectoInfo;
    cotizacion: CotizacionInfo;
    archivoId: number | null;
    pdfUrl: string | null;
    pdfNombre: string | null;
    subidoEl: string | null;
}>();

const rutaOc = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    cotizacion: props.cotizacion.id,
}));

const archivoInput = ref<HTMLInputElement | null>(null);
const estatus = ref<EstadoCompra>(props.cotizacion.estatusCompra);
const guardandoEstatus = ref(false);
const archivoSeleccionado = ref('');

const estadosCompra: Array<{
    value: EstadoCompra;
    label: string;
    description: string;
}> = [
        {
            value: 'pendiente',
            label: 'Pendiente',
            description: 'Aún no se ha definido el cierre.',
        },
        {
            value: 'aprobado',
            label: 'Completado',
            description: 'El proyecto puede cerrarse aunque falte el PDF.',
        },
        {
            value: 'rechazado',
            label: 'Rechazado',
            description: 'La compra no continúa por ahora.',
        },
    ];

const estadoActual = computed(
    () =>
        estadosCompra.find(
            (estado) => estado.value === props.cotizacion.estatusCompra,
        ) ?? estadosCompra[0],
);
const esCompletado = computed(
    () => props.cotizacion.estatusCompra === 'aprobado',
);
const tieneDocumento = computed(() => Boolean(props.pdfUrl));

function guardarEstatus(): void {
    guardandoEstatus.value = true;
    router.post(
        CotizacionController.actualizarEstatusCompraProyecto(rutaOc.value).url,
        { estatus_compra: estatus.value },
        {
            preserveScroll: true,
            onFinish: () => (guardandoEstatus.value = false),
        },
    );
}



function subirArchivo(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;
    archivoSeleccionado.value = archivo.name;

    router.post(
        CotizacionController.subirAutorizacionProyecto(rutaOc.value).url,
        { archivo },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                archivoSeleccionado.value = '';
                if (archivoInput.value) archivoInput.value.value = '';
            },
        },
    );
}

function eliminarPdf(): void {
    if (!props.archivoId) return;
    router.delete(ArchivoController.destroy(props.archivoId).url, {
        preserveScroll: true,
    });
}

function volver(): void {
    router.visit('..');
}
</script>

<template>

    <Head :title="`OC · ${cotizacion.folio}`" />

    <PageLayout title="" description="">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex items-center gap-3 text-sm text-muted-foreground">
                <Button variant="ghost" size="sm" class="-ml-3" @click="volver">
                    <ChevronLeft class="mr-1 size-4" /> Volver
                </Button>
                <span>/</span>
                <span class="font-medium text-foreground">Gestión de OC</span>
            </div>

            <header class="rounded-3xl border bg-card p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm">
                            <ShoppingCart class="size-7" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">
                                Orden de compra
                            </p>
                            <h1 class="mt-1 text-3xl font-bold tracking-tight">
                                {{ cotizacion.folio }}
                            </h1>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{
                                    cotizacion.obra ||
                                    'Proyecto sin obra especificada'
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-muted/50 px-5 py-4 lg:min-w-56">
                        <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                            Estado del proyecto
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="flex size-7 items-center justify-center rounded-full" :class="esCompletado
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                ">
                                <Check v-if="esCompletado" class="size-4" />
                                <CircleAlert v-else class="size-4" />
                            </span>
                            <span class="font-semibold">{{
                                estadoActual.label
                            }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <div v-if="esCompletado && !tieneDocumento"
                class="mt-6 flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                <CircleAlert class="mt-0.5 size-5 shrink-0 text-amber-600" />
                <div>
                    <p class="font-semibold">
                        El proyecto está completado, pero falta la OC
                    </p>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                        Puedes cargar el PDF cuando esté disponible sin cambiar
                        el estado del proyecto.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Save class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-semibold">Cambiar estado</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Actualiza manualmente el estado cuando
                                corresponda.
                            </p>
                        </div>
                    </div>
                    <label class="mt-6 flex flex-col gap-2 text-sm font-medium">
                        Estado del proyecto
                        <select v-model="estatus"
                            class="h-11 rounded-xl border border-input bg-background px-3 outline-none focus:ring-2 focus:ring-ring">
                            <option v-for="estado in estadosCompra" :key="estado.value" :value="estado.value">
                                {{ estado.label }}
                            </option>
                        </select>
                    </label>
                    <Button class="mt-4 w-full" :disabled="guardandoEstatus ||
                        estatus === cotizacion.estatusCompra
                        " @click="guardarEstatus">
                        <Save class="mr-2 size-4" />
                        {{ guardandoEstatus ? 'Guardando…' : 'Guardar estado' }}
                    </Button>
                    <p v-if="cotizacion.fechaEstatus" class="mt-4 text-center text-xs text-muted-foreground">
                        Última actualización: {{ cotizacion.fechaEstatus }}
                    </p>
                </section>

                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <FileText class="size-5" />
                            </div>
                            <div>
                                <h2 class="font-semibold">Documento de OC</h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Carga o reemplaza el PDF sin afectar el
                                    estado.
                                </p>
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="tieneDocumento
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                            ">{{
                                tieneDocumento ? 'Cargado' : 'Pendiente'
                            }}</span>
                    </div>

                    <div v-if="tieneDocumento" class="mt-6 rounded-xl border bg-muted/30 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <FileCheck2 class="size-5 shrink-0 text-emerald-600" />
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ pdfNombre }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Cargado el {{ subidoEl || '—' }}
                                    </p>
                                </div>
                            </div>
                            <a :href="pdfUrl || undefined" target="_blank" rel="noopener noreferrer"
                                class="shrink-0 text-sm font-medium text-primary hover:underline">Ver PDF</a>
                        </div>
                    </div>

                    <label
                        class="mt-4 flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-border p-5 text-sm font-medium text-muted-foreground transition hover:border-primary hover:bg-primary/5">
                        <Upload class="size-5 text-primary" />
                        <span>{{
                            tieneDocumento
                                ? 'Seleccionar otro PDF'
                                : 'Seleccionar PDF de la OC'
                        }}</span>
                        <input ref="archivoInput" type="file" accept="application/pdf" class="hidden"
                            @change="subirArchivo" />
                    </label>
                    <p v-if="archivoSeleccionado" class="mt-3 text-center text-xs text-muted-foreground">
                        Subiendo {{ archivoSeleccionado }}…
                    </p>
                    <Button v-if="tieneDocumento" variant="ghost"
                        class="mt-2 w-full text-destructive hover:bg-destructive/10" @click="eliminarPdf">Quitar
                        documento</Button>
                </section>
            </div>
        </div>
    </PageLayout>
</template>
