<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsCotizacion, type CotizacionRef, type LevantamientoRef, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
    cotizacion: CotizacionRef;
}

export default pageLayout(() => {
    const { planta, proyecto, levantamiento, cotizacion } = usePage<Props>().props;
    return breadcrumbsCotizacion(planta, proyecto, levantamiento, cotizacion);
});
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Box, Building2, Clock, DollarSign, Download, FileText, Hash, Lock, MapPin, ShoppingCart, User } from '@lucide/vue';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';

interface PlantaResumen { id: number; nombre: string }
interface ProyectoResumen { id: number; nombre: string; folio: string; bloqueado: boolean; motivo_bloqueo: string | null }
interface LevantamientoResumen { id: number; folio: string }

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
    costo_hora_total?: number | null;
    estado: string;
    creado: string | null;
    tiene_insumos: boolean;
    tiene_orden_compra: boolean;
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
    planta: PlantaResumen;
    proyecto: ProyectoResumen;
    levantamiento: LevantamientoResumen;
    cotizacion: CotizacionDetalle;
    partidas: PartidaRaiz[];
    numeroPartidas: number;
}>();

const estadoLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    aprobada: 'Aprobada',
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
            <Link
                :href="LevantamientoController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id })"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <!-- Header morado -->
        <div class="overflow-hidden rounded-2xl border shadow-sm">
            <div
                class="flex flex-wrap items-start justify-between gap-4 bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white">
                <div>
                    <p class="text-2xl font-bold">{{ cotizacion.folio }}</p>
                    <p class="mt-1 text-sm text-indigo-100">{{ cotizacion.fecha ?? '—' }}</p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <Button variant="secondary" size="sm">
                        <Download class="mr-2 size-4" />
                        Exportar
                    </Button>
                    <span class="rounded-md bg-emerald-500 px-3 py-1 text-xs font-bold uppercase">
                        {{ estadoLabel[cotizacion.estado] ?? cotizacion.estado }}
                    </span>
                </div>
            </div>

            <!-- Banner aprobado sin OC -->
            <div v-if="cotizacion.estado === 'aprobada' && !cotizacion.tiene_orden_compra"
                class="flex items-start gap-3 bg-emerald-50 px-6 py-4 dark:bg-emerald-950/30">
                <ShoppingCart class="mt-0.5 size-5 shrink-0 text-emerald-600" />
                <div>
                    <p class="font-semibold text-emerald-800 dark:text-emerald-300">Aprobado sin orden de compra</p>
                    <p class="text-sm text-emerald-700 dark:text-emerald-400">Esta cotización fue aprobada por un
                        administrador
                        sin orden de compra.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-6 border-b bg-card px-6 py-5 sm:grid-cols-4">
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
                <div class="flex items-start gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600">
                        <Clock class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Costo/Hora Total</p>
                        <p class="font-bold">{{ formatoMoneda(cotizacion.costo_hora_total) }}</p>
                    </div>
                </div>
            </div>

            <!-- Info cliente / proyecto -->
            <div class="grid gap-4 border-b bg-card px-6 py-5 sm:grid-cols-2">
                <div class="rounded-lg bg-muted/40 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase text-muted-foreground">Información del Cliente</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <Building2 class="size-4 text-muted-foreground" /><span class="font-medium">Cliente:</span>
                            {{
                            cotizacion.cliente ?? '—' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin class="size-4 text-muted-foreground" /><span class="font-medium">Dirección:</span>
                            {{
                            cotizacion.direccion ?? '—' }}
                        </div>
                    </dl>
                </div>
                <div class="rounded-lg bg-muted/40 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase text-muted-foreground">Información del Proyecto</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <FileText class="size-4 text-muted-foreground" /><span class="font-medium">Obra:</span> {{
                            cotizacion.obra ?? '—' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <User class="size-4 text-muted-foreground" /><span class="font-medium">Vendedor:</span> {{
                            cotizacion.vendedor ?? '—' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Hash class="size-4 text-muted-foreground" /><span class="font-medium">Proveedor:</span> {{
                            cotizacion.proveedor ?? '—' }}
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Info adicional -->
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

        <!-- Proceso de Cotización (display-only por ahora) -->
        <div class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <p class="text-lg font-semibold">Proceso de Cotización</p>
            <p class="mb-5 text-sm text-muted-foreground">Completa las fases en orden para procesar la cotización</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border p-4"
                    :class="cotizacion.tiene_insumos ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20' : ''">
                    <div class="mb-3 flex items-center gap-2">
                        <Box class="size-5"
                            :class="cotizacion.tiene_insumos ? 'text-emerald-600' : 'text-muted-foreground'" />
                        <div>
                            <p class="font-semibold">Explosión de Insumos</p>
                            <p class="text-xs text-muted-foreground">Materiales definidos y listos</p>
                        </div>
                    </div>
                    <p v-if="cotizacion.tiene_insumos" class="text-sm text-emerald-700 dark:text-emerald-400">Fase
                        completada
                    </p>
                    <p v-else class="text-sm text-muted-foreground">Pendiente</p>
                </div>

                <div class="rounded-xl border p-4"
                    :class="cotizacion.tiene_orden_compra ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20' : ''">
                    <div class="mb-3 flex items-center gap-2">
                        <ShoppingCart class="size-5"
                            :class="cotizacion.tiene_orden_compra ? 'text-emerald-600' : 'text-muted-foreground'" />
                        <div>
                            <p class="font-semibold">Orden de Compra</p>
                            <p class="text-xs text-muted-foreground">Orden emitida y aprobada</p>
                        </div>
                    </div>
                    <p v-if="cotizacion.tiene_orden_compra" class="text-sm text-emerald-700 dark:text-emerald-400">Orden
                        aprobada</p>
                    <p v-else class="text-sm text-muted-foreground">Pendiente</p>
                </div>
            </div>

            <div v-if="proyecto.bloqueado"
                class="mt-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900 dark:bg-red-950/30">
                <Lock class="mt-0.5 size-4 shrink-0 text-red-600" />
                <div>
                    <p class="text-sm font-semibold text-red-800 dark:text-red-300">Estado del Proyecto: BLOQUEADO</p>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ proyecto.motivo_bloqueo ?? 'Este proyecto está
                        bloqueado y no permite nuevas cotizaciones.' }}</p>
                </div>
            </div>
        </div>

        <!-- Partidas -->
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
