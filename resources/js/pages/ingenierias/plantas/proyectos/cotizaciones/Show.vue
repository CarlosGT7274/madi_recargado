<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsCotizacionDirecto, type CotizacionRef, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    cotizacion: CotizacionRef & { obra?: string | number | null };
}

export default pageLayout(() => {
    const { planta, proyecto, cotizacion } = usePage<Props>().props;
    return breadcrumbsCotizacionDirecto(planta, proyecto, cotizacion);
});
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight, Building2, CheckCircle2, Clock, DollarSign, Download,
    FileText, Hash, MapPin, ShoppingCart, Upload, User,
} from '@lucide/vue';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import { computed, ref } from 'vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string; folio: string }

interface CotizacionDetalle {
    id: number;
    folio: string;
    fecha: string | null;
    cliente: string | null;
    direccion: string | null;
    obra: string | null;
    vendedor: string | null;
    proveedor: string | null;
    subtotal: number | null;
    iva: number | null;
    total: number | null;
    estado: string;
    creado: string | null;
    completada: boolean;
    estatusCompra: string;
    pdfAutorizacion: string | null;
}

interface PartidaHija {
    id: number;
    no: string;
    descripcion: string;
    unidad: string | null;
    cantidad: number;
    precioUnitario: number;
    importe: number;
}

interface PartidaRaiz {
    id: number;
    no: string;
    descripcion: string;
    hijas: PartidaHija[];
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    cotizacion: CotizacionDetalle;
    partidas: PartidaRaiz[];
    numeroPartidas: number;
}>();

const rutaOc = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    cotizacion: props.cotizacion.id,
}));

const archivoOcInput = ref<HTMLInputElement | null>(null);

function subirOrdenCompra(): void {
    const archivo = archivoOcInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.subirAutorizacionProyecto(rutaOc.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true },
    );
}

const estadoLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    rechazada: 'Rechazada',
};

function formatoMoneda(valor: number | null | undefined): string {
    if (valor === null || valor === undefined) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>

    <Head :title="`Cotización ${cotizacion.folio}`" />

    <PageLayout title="" description="">
        <template #breadcrumbs>
            <Link :href="ProyectoController.show([planta.id, proyecto.id])"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowRight class="size-4 rotate-180" />
            </Link>
        </template>

        <div class="overflow-hidden rounded-2xl border shadow-sm">
            <div
                class="flex flex-wrap items-start justify-between gap-4 bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white">
                <div>
                    <p class="text-2xl font-bold">{{ cotizacion.folio }}</p>
                    <p class="mt-1 text-sm text-indigo-100">{{ cotizacion.fecha ?? '—' }}</p>
                </div>
                <span class="rounded-md px-3 py-1 text-xs font-bold uppercase"
                    :class="cotizacion.completada ? 'bg-emerald-500' : 'bg-white/20'">
                    {{ cotizacion.completada ? 'Completada' : (estadoLabel[cotizacion.estado] ?? cotizacion.estado) }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-6 border-b bg-card px-6 py-5 sm:grid-cols-3">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600">
                        <DollarSign class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total</p>
                        <p class="font-bold">{{ formatoMoneda(cotizacion.total) }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                        <DollarSign class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Subtotal</p>
                        <p class="font-bold">{{ formatoMoneda(cotizacion.subtotal) }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                        <DollarSign class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">IVA</p>
                        <p class="font-bold">{{ formatoMoneda(cotizacion.iva) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 border-b bg-card px-6 py-5 sm:grid-cols-2">
                <div class="rounded-lg bg-muted/40 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase text-muted-foreground">Información del Cliente</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <Building2 class="size-4 text-muted-foreground" /><span class="font-medium">Cliente:</span>
                            {{ cotizacion.cliente ?? '—' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin class="size-4 text-muted-foreground" /><span class="font-medium">Dirección:</span>
                            {{ cotizacion.direccion ?? '—' }}
                        </div>
                    </dl>
                </div>
                <div class="rounded-lg bg-muted/40 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase text-muted-foreground">Información del Proyecto</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <FileText class="size-4 text-muted-foreground" /><span class="font-medium">Obra:</span>
                            {{ cotizacion.obra ?? '—' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <User class="size-4 text-muted-foreground" /><span class="font-medium">Vendedor:</span>
                            {{ cotizacion.vendedor ?? '—' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Hash class="size-4 text-muted-foreground" /><span class="font-medium">Proveedor:</span>
                            {{ cotizacion.proveedor ?? '—' }}
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-card px-6 py-5">
                <p class="mb-3 text-xs font-semibold uppercase text-muted-foreground">Información Adicional</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Número de Partidas</p>
                        <p class="font-medium">{{ numeroPartidas }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Folio</p>
                        <p class="font-medium">{{ cotizacion.folio }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Fecha de Creación</p>
                        <p class="font-medium">{{ cotizacion.creado ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <ShoppingCart class="size-5" />
                <p class="text-lg font-semibold">Orden de Compra</p>
            </div>

            <div v-if="cotizacion.pdfAutorizacion"
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                        <CheckCircle2 class="size-4" />
                    </div>
                    <div>
                        <p class="font-medium">Orden de compra cargada</p>
                    </div>
                </div>
                <a :href="cotizacion.pdfAutorizacion" target="_blank">
                    <Button variant="outline" size="sm">
                        <Download class="mr-1.5 size-3.5" />
                        Descargar
                    </Button>
                </a>
            </div>

            <label v-else
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed py-10 text-sm font-medium text-muted-foreground hover:bg-accent">
                <Upload class="size-5" />
                <span class="text-primary">Seleccionar PDF</span>
                <span class="text-xs">Con solo subirlo, este requisito queda cumplido</span>
                <input ref="archivoOcInput" type="file" accept="application/pdf" class="hidden"
                    @change="subirOrdenCompra" />
            </label>

            <div class="mt-5 flex items-start gap-3 rounded-lg border px-4 py-3" :class="cotizacion.completada
                ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30'
                : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30'">
                <component :is="cotizacion.completada ? CheckCircle2 : Clock" class="mt-0.5 size-4 shrink-0"
                    :class="cotizacion.completada ? 'text-emerald-600' : 'text-amber-600'" />
                <p class="text-sm font-medium"
                    :class="cotizacion.completada ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300'">
                    {{ cotizacion.completada ? 'Cotización completada' : 'Falta subir la orden de compra' }}
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <FileText class="size-5" />
                <p class="text-lg font-semibold">Partidas de la Cotización</p>
            </div>

            <div v-for="raiz in partidas" :key="raiz.id" class="mb-6 overflow-hidden rounded-xl border">
                <div class="border-b bg-muted/50 px-4 py-3">
                    <p class="font-semibold">{{ raiz.no }} · {{ raiz.descripcion }}</p>
                    <p class="text-xs text-muted-foreground">{{ raiz.hijas.length }} partidas</p>
                </div>

                <div
                    class="grid grid-cols-[60px_1fr_80px_90px_110px_110px] gap-2 px-4 py-2 text-xs font-medium text-muted-foreground">
                    <span>No.</span>
                    <span>Concepto</span>
                    <span>Unidad</span>
                    <span>Cantidad</span>
                    <span class="text-right">P. Unitario</span>
                    <span class="text-right">Total</span>
                </div>

                <div v-for="hija in raiz.hijas" :key="hija.id"
                    class="grid grid-cols-[60px_1fr_80px_90px_110px_110px] items-center gap-2 border-t px-4 py-3 text-sm">
                    <span class="text-muted-foreground">{{ hija.no }}</span>
                    <span class="truncate">{{ hija.descripcion }}</span>
                    <span class="text-muted-foreground">{{ hija.unidad ?? '—' }}</span>
                    <span>{{ hija.cantidad }}</span>
                    <span class="text-right">{{ formatoMoneda(hija.precioUnitario) }}</span>
                    <span class="text-right font-medium">{{ formatoMoneda(hija.importe) }}</span>
                </div>
            </div>

            <p v-if="!partidas.length" class="py-8 text-center text-sm text-muted-foreground">Aún no hay partidas.</p>
        </div>
    </PageLayout>
</template>
