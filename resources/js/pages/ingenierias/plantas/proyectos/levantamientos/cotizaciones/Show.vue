<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsCotizacion, type CotizacionRef, type LevantamientoRef, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';
import PermissionButton from '@/components/PermissionButton.vue';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
    cotizacion: CotizacionRef & { obra?: string | number | null };
}

export default pageLayout(() => {
    const { planta, proyecto, levantamiento, cotizacion } = usePage<Props>().props;
    return breadcrumbsCotizacion(planta, proyecto, levantamiento, cotizacion, cotizacion.obra);
});
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Box,
    Building2,
    CheckCircle2,
    Clock,
    DollarSign,
    Download,
    FileText,
    FileWarning,
    Hash,
    MapPin,
    Send,
    ShieldCheck,
    ShoppingCart,
    Trash2,
    Upload,
    User,
    XCircle,
} from '@lucide/vue';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import { computed, onMounted, ref } from 'vue';
import InsumoController from '@/actions/App/Http/Controllers/Ingenierias/InsumoController';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import { usePermissions } from '@/composables/usePermissions';

interface PlantaResumen { id: number; nombre: string }
interface LevantamientoResumen { id: number; folio: string }

interface ProyectoResumen {
    id: number;
    nombre: string;
    folio: string;
    completado: boolean;
}

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
    planta: PlantaResumen;
    proyecto: ProyectoResumen;
    levantamiento: LevantamientoResumen;
    cotizacion: CotizacionDetalle;
    partidas: PartidaRaiz[];
    numeroPartidas: number;
}>();

const { hasPermission, Accion } = usePermissions();
const endpoint = 'ingenierias.plantas.proyectos.levantamientos.cotizaciones';
const puedeEliminar = computed(() => hasPermission(endpoint, Accion.DELETE));

const rutaOc = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
    cotizacion: props.cotizacion.id,
}));

const urlPdf = computed(() => CotizacionController.pdf(rutaOc.value).url);

const ocDialogOpen = ref(false);
const archivoOcInput = ref<HTMLInputElement | null>(null);

const ocHabilitada = computed(() => props.cotizacion.tiene_insumos);

const solicitudPendiente = computed(
    () => props.cotizacion.estatusCompra === 'en_cotizacion' && !props.cotizacion.completada,
);

const estadoDescripcion = computed(() =>
    props.cotizacion.completada
        ? 'Insumos y Orden de Compra están completos.'
        : 'Faltan pasos por completar en esta cotización.'
);

/**
 * `cotizacion.tiene_insumos`/`completada` se calculan en el servidor y se
 * envían como snapshot estático. Cuando el usuario navega con "atrás" del
 * navegador, Inertia puede restaurar esta página desde el caché del
 * historial en vez de pedir datos frescos, dejando el badge "Fase
 * completada" desfasado hasta la siguiente visita real. Forzamos un
 * refresh puntual de esta prop al montar para que nunca se quede vieja.
 */
onMounted(() => {
    router.reload({ only: ['cotizacion'] });
});

function subirOrdenCompra(): void {
    const archivo = archivoOcInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.subirAutorizacion(rutaOc.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true, onSuccess: () => (ocDialogOpen.value = false) },
    );
}

function solicitarRevision(): void {
    router.post(CotizacionController.solicitarRevisionCompra(rutaOc.value).url, {}, { preserveScroll: true });
}

function aprobarRevision(): void {
    router.post(CotizacionController.aprobarCompra(rutaOc.value).url, {}, { preserveScroll: true });
}

function rechazarRevision(): void {
    router.post(CotizacionController.rechazarCompra(rutaOc.value).url, {}, { preserveScroll: true });
}

function eliminar(): void {
    if (!confirm(`¿Eliminar la cotización "${props.cotizacion.folio}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(CotizacionController.destroy(rutaOc.value).url);
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

    <PageLayout title="" description="" endpoint="ingenierias.plantas.proyectos.levantamientos.cotizaciones">
        <template #breadcrumbs>
            <Link
                :href="LevantamientoController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id }).url"
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

                <div class="flex flex-col items-end gap-2">
                    <div class="flex items-center gap-2">
                        <a :href="urlPdf" target="_blank" rel="noopener noreferrer">
                            <Button variant="secondary" size="sm">
                                <Download class="mr-2 size-4" />
                                Exportar
                            </Button>
                        </a>

                        <PermissionButton endpoint="ingenierias.plantas.proyectos.levantamientos.cotizaciones"
                            :accion="Accion.DELETE" variant="secondary" size="sm" @click="eliminar">
                            <Trash2 class="mr-2 size-4" />
                            Eliminar
                        </PermissionButton>
                    </div>

                    <span class="rounded-md px-3 py-1 text-xs font-bold uppercase"
                        :class="cotizacion.completada ? 'bg-emerald-500' : 'bg-white/20'">
                        {{ cotizacion.completada ? 'Completada' : (estadoLabel[cotizacion.estado] ?? cotizacion.estado)
                        }}
                    </span>
                </div>
            </div>

            <div v-if="solicitudPendiente"
                class="border-b border-amber-200 bg-amber-50 px-6 py-5 dark:border-amber-900 dark:bg-amber-950/30">
                <div class="flex items-start gap-3">
                    <Clock class="mt-0.5 size-5 shrink-0 text-amber-600" />
                    <div>
                        <p class="font-semibold text-amber-800 dark:text-amber-300">Pendiente de revisión administrativa
                        </p>
                        <p class="text-sm text-amber-700 dark:text-amber-400">
                            Esta cotización fue enviada para revisión sin orden de compra.
                        </p>
                    </div>
                </div>

                <div v-if="hasPermission(endpoint, 'aprobar')" class="mt-4 border-t border-amber-200 pt-4 dark:border-amber-900">
                    <p class="mb-2 text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">
                        Acciones de administrador
                    </p>
                    <div class="flex gap-2">
                        <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="aprobarRevision">
                            <CheckCircle2 class="mr-2 size-4" />
                            Aprobar
                        </Button>
                        <Button variant="outline"
                            class="border-red-300 text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/30"
                            @click="rechazarRevision">
                            <XCircle class="mr-2 size-4" />
                            Rechazar
                        </Button>
                    </div>
                </div>
            </div>

            <div v-else-if="cotizacion.estatusCompra === 'rechazado'"
                class="flex items-start gap-3 border-b border-red-200 bg-red-50 px-6 py-5 dark:border-red-900 dark:bg-red-950/30">
                <FileWarning class="mt-0.5 size-5 shrink-0 text-red-600" />
                <div>
                    <p class="font-semibold text-red-800 dark:text-red-300">Solicitud rechazada</p>
                    <p class="text-sm text-red-700 dark:text-red-400">
                        La revisión sin orden de compra fue rechazada. Sube el PDF de la orden para continuar.
                    </p>
                </div>
            </div>

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
            <p class="text-lg font-semibold">Proceso de Cotización</p>
            <p class="mb-5 text-sm text-muted-foreground">Completa las fases en orden para procesar la cotización</p>

            <div class="mb-5 flex items-center justify-between text-sm font-medium">
                <span class="flex items-center gap-1.5"
                    :class="cotizacion.tiene_insumos ? 'text-emerald-600' : 'text-muted-foreground'">
                    <span class="flex size-6 items-center justify-center rounded-full"
                        :class="cotizacion.tiene_insumos ? 'bg-emerald-100 text-emerald-600' : 'bg-muted'">
                        <CheckCircle2 v-if="cotizacion.tiene_insumos" class="size-4" />
                        <span v-else class="text-xs">1</span>
                    </span>
                    Explosión de Insumos
                </span>
                <ArrowRight class="size-4 text-muted-foreground" />
                <span class="flex items-center gap-1.5"
                    :class="cotizacion.completada ? 'text-emerald-600' : 'text-muted-foreground'">
                    <span class="flex size-6 items-center justify-center rounded-full"
                        :class="cotizacion.completada ? 'bg-emerald-100 text-emerald-600' : 'bg-muted'">
                        <CheckCircle2 v-if="cotizacion.completada" class="size-4" />
                        <span v-else class="text-xs">2</span>
                    </span>
                    Orden de Compra
                </span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border p-4"
                    :class="cotizacion.tiene_insumos ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : ''">
                    <div class="mb-3 flex items-center gap-2">
                        <Box class="size-5"
                            :class="cotizacion.tiene_insumos ? 'text-emerald-600' : 'text-muted-foreground'" />
                        <div>
                            <p class="font-semibold">Explosión de Insumos</p>
                            <p class="text-xs text-muted-foreground">Materiales definidos y listos</p>
                        </div>
                    </div>

                    <div v-if="cotizacion.tiene_insumos"
                        class="mb-3 rounded-lg bg-white/60 px-3 py-2 text-sm dark:bg-black/20">
                        <p class="font-medium text-emerald-700 dark:text-emerald-400">✓ Fase completada</p>
                        <p class="text-xs text-muted-foreground">Los materiales han sido registrados correctamente</p>
                    </div>

                    <Link
                        :href="InsumoController.index({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id, cotizacion: cotizacion.id }).url">
                        <Button class="w-full"
                            :class="cotizacion.tiene_insumos ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ''"
                            :variant="cotizacion.tiene_insumos ? 'default' : 'outline'">
                            Ver Insumos
                        </Button>
                    </Link>
                </div>

                <div class="rounded-xl border p-4"
                    :class="[!ocHabilitada && 'opacity-60', cotizacion.completada ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : '']">
                    <div class="mb-3 flex items-center gap-2">
                        <ShoppingCart class="size-5"
                            :class="cotizacion.completada ? 'text-emerald-600' : 'text-muted-foreground'" />
                        <div>
                            <p class="font-semibold">Orden de Compra</p>
                            <p class="text-xs text-muted-foreground">Sube el PDF de la orden o solicita revisión</p>
                        </div>
                    </div>

                    <template v-if="!ocHabilitada">
                        <p class="text-sm text-muted-foreground">Primero completa la Explosión de Insumos para habilitar
                            este paso.</p>
                    </template>

                    <template v-else-if="cotizacion.completada">
                        <div class="rounded-lg bg-white/60 px-3 py-2 text-sm dark:bg-black/20">
                            <p class="font-medium text-emerald-700 dark:text-emerald-400">✓ Fase completada</p>
                        </div>
                        <Link class="mt-3 block" :href="CotizacionController.ordenCompra(rutaOc).url">
                            <Button class="w-full bg-emerald-600 text-white hover:bg-emerald-700">Ver Orden de
                                Compra</Button>
                        </Link>
                    </template>

                    <template v-else-if="solicitudPendiente">
                        <div
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/20">
                            <p class="font-medium text-amber-700 dark:text-amber-400">Solicitud enviada</p>
                            <p class="text-xs text-muted-foreground">En espera de aprobación de un administrador.</p>
                        </div>
                    </template>

                    <template v-else>
                        <div
                            class="mb-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950/20">
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Opción 1: Subir orden de
                                compra</p>
                            <p class="mb-2 text-xs text-muted-foreground">Sube el PDF de la orden de compra</p>
                            <Button class="w-full" @click="ocDialogOpen = true">
                                <ShoppingCart class="mr-2 size-4" />
                                Subir PDF
                            </Button>
                        </div>

                        <div
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/20">
                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Opción 2: Solicitar
                                revisión sin orden</p>
                            <p class="mb-2 text-xs text-muted-foreground">Envía esta cotización para revisión
                                administrativa</p>
                            <Button class="w-full bg-amber-600 text-white hover:bg-amber-700"
                                @click="solicitarRevision">
                                <Send class="mr-2 size-4" />
                                Solicitar Revisión
                            </Button>
                        </div>
                    </template>
                </div>

                <Dialog v-model:open="ocDialogOpen">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Subir Orden de Compra</DialogTitle>
                            <DialogDescription>Selecciona el PDF de la orden de compra.</DialogDescription>
                        </DialogHeader>

                        <label
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed py-10 text-sm font-medium text-muted-foreground hover:bg-accent">
                            <Upload class="size-5" />
                            <span class="text-primary">Seleccionar PDF</span>
                            <input ref="archivoOcInput" type="file" accept="application/pdf" class="hidden"
                                @change="subirOrdenCompra" />
                        </label>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="secondary">Cancelar</Button>
                            </DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="mt-5 flex items-start gap-3 rounded-lg border px-4 py-3" :class="cotizacion.completada
                ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30'
                : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30'">
                <component :is="cotizacion.completada ? CheckCircle2 : Clock" class="mt-0.5 size-4 shrink-0"
                    :class="cotizacion.completada ? 'text-emerald-600' : 'text-amber-600'" />
                <div>
                    <p class="text-sm font-semibold"
                        :class="cotizacion.completada ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300'">
                        {{ cotizacion.completada ? 'Flujo aprobado' : 'Flujo en proceso' }}
                    </p>
                    <p class="text-sm"
                        :class="cotizacion.completada ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                        {{ estadoDescripcion }}
                    </p>
                </div>
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
