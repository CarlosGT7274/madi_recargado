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
    return [
        ...breadcrumbsCotizacion(planta, proyecto, levantamiento, cotizacion),
        { title: 'Explosión de Insumos', href: '' },
    ];
});
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Boxes, Check, Download, FileSpreadsheet, Loader2, PencilLine, Table2, Upload, X } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import InsumoController from '@/actions/App/Http/Controllers/Ingenierias/InsumoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import PermissionInput from '@/components/PermissionInput.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string }
interface LevantamientoRef { id: number; folio: string }
interface CotizacionRef { id: number; folio: string; obra: string | null; total: number }

type Categoria = 'materiales' | 'mano_obra' | 'maquinaria';

interface InsumoItem {
    id: number;
    codigo: string;
    concepto: string;
    unidad: string;
    categoria: Categoria;
    cantidad: number;
    precio: number | null;
    importe: number;
    estatus: string;
}

interface Resumen {
    total: number;
    materiales: number;
    manoObra: number;
    maquinaria: number;
    requisitados: number;
    subtotal: number;
    iva: number;
    totalConIva: number;
    totalCotizacion: number;
    utilidadEstimada: number;
    margenEstimado: number | null;
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    levantamiento: LevantamientoRef;
    cotizacion: CotizacionRef;
    insumos: InsumoItem[];
    resumen: Resumen;
}>();

const endpointInsumos = 'ingenierias.plantas.proyectos.levantamientos.cotizaciones.insumos';
const { hasPermission, Accion } = usePermissions();
const puedeEditar = computed(() => hasPermission(endpointInsumos, Accion.UPDATE));

const rutaInsumos = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
    cotizacion: props.cotizacion.id,
}));

// --- Edición inline ---
const modo = ref<'view' | 'edit'>('view');

type FilaEditable = { concepto: string; unidad: string; cantidad: string; precio: string };
const filas = reactive<Record<number, FilaEditable>>({});
const estadoGuardado = reactive<Record<number, 'idle' | 'guardando' | 'guardado'>>({});

function sincronizarFilas(): void {
    for (const insumo of props.insumos) {
        // No pisamos una fila mientras el usuario la sigue editando activamente.
        if (estadoGuardado[insumo.id] === 'guardando') continue;

        filas[insumo.id] = {
            concepto: insumo.concepto,
            unidad: insumo.unidad,
            cantidad: String(insumo.cantidad),
            precio: insumo.precio !== null ? String(insumo.precio) : '',
        };
    }
}

watch(() => props.insumos, sincronizarFilas, { immediate: true, deep: true });

function importeLocal(insumo: InsumoItem): number {
    const fila = filas[insumo.id];
    if (!fila) return insumo.importe;

    const cantidad = parseFloat(fila.cantidad) || 0;
    const precio = parseFloat(fila.precio) || 0;

    return cantidad * precio;
}

function guardarInsumo(insumo: InsumoItem): void {
    const fila = filas[insumo.id];
    if (!fila) return;

    estadoGuardado[insumo.id] = 'guardando';

    router.put(
        InsumoController.update({ ...rutaInsumos.value, insumo: insumo.id }).url,
        {
            concepto: fila.concepto,
            unidad: fila.unidad,
            cantidad_presupuestada: parseFloat(fila.cantidad) || 0,
            precio: fila.precio === '' ? null : parseFloat(fila.precio) || 0,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['insumos', 'resumen'],
            onSuccess: () => {
                estadoGuardado[insumo.id] = 'guardado';
                setTimeout(() => {
                    if (estadoGuardado[insumo.id] === 'guardado') {
                        estadoGuardado[insumo.id] = 'idle';
                    }
                }, 1500);
            },
            onError: () => {
                estadoGuardado[insumo.id] = 'idle';
            },
        },
    );
}

// --- Modal de importación ---
type TipoPlantilla = 'propia' | 'externa';

const infoFormato: Record<TipoPlantilla, { titulo: string; descripcion: string; icon: typeof FileSpreadsheet }> = {
    propia: {
        titulo: 'Plantilla MADI',
        descripcion: 'Una sola hoja con Categoría, Código, Concepto, Unidad, Cantidad, Precio e Importe.',
        icon: FileSpreadsheet,
    },
    externa: {
        titulo: 'Formato Walmart',
        descripcion: 'Una hoja por categoría (Materiales, Mano de Obra, Maquinaria). Cada hoja se procesa por separado.',
        icon: Table2,
    },
};

const importDialogOpen = ref(false);
const tipoPlantilla = ref<TipoPlantilla>('propia');
const archivoInput = ref<HTMLInputElement | null>(null);
const subiendo = ref(false);
const arrastrando = ref(false);
const archivoSeleccionado = ref<string | null>(null);

function abrirSelectorArchivo(): void {
    archivoInput.value?.click();
}

function onInputChange(event: Event): void {
    const archivo = (event.target as HTMLInputElement).files?.[0];
    if (archivo) procesarArchivo(archivo);
}

function onDrop(event: DragEvent): void {
    arrastrando.value = false;
    const archivo = event.dataTransfer?.files?.[0];
    if (archivo) procesarArchivo(archivo);
}

function procesarArchivo(archivo: File): void {
    if (!/\.(xlsx|xls)$/i.test(archivo.name)) {
        return;
    }

    archivoSeleccionado.value = archivo.name;
    subiendo.value = true;

    router.post(
        InsumoController.importar(rutaInsumos.value).url,
        { archivo, tipo_plantilla: tipoPlantilla.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                importDialogOpen.value = false;
            },
            onFinish: () => {
                subiendo.value = false;
                archivoSeleccionado.value = null;
                if (archivoInput.value) archivoInput.value.value = '';
            },
        },
    );
}

const secciones: { categoria: Categoria; titulo: string }[] = [
    { categoria: 'materiales', titulo: 'Listado de Materiales' },
    { categoria: 'mano_obra', titulo: 'Listado de Mano de Obra' },
    { categoria: 'maquinaria', titulo: 'Listado de Maquinaria' },
];

function insumosDe(categoria: Categoria): InsumoItem[] {
    return props.insumos.filter((i) => i.categoria === categoria);
}

function formatoMoneda(valor: number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}

function estatusBadgeClass(estatus: string): string {
    if (estatus === 'requisitado' || estatus === 'comprado' || estatus === 'entregado') {
        return 'bg-emerald-500/10 text-emerald-600';
    }

    return 'bg-amber-500/10 text-amber-600';
}
</script>

<template>

    <Head title="Explosión de Insumos" />

    <PageLayout title="" description="">
        <template #breadcrumbs>
            <Link :href="CotizacionController.show(rutaInsumos).url"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <!-- Modal: Subir Excel de Insumos -->
        <Dialog v-model:open="importDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Upload class="size-4 text-violet-600" />
                        Subir Excel de Insumos
                    </DialogTitle>
                    <DialogDescription>
                        Elige el formato del archivo y arrástralo o selecciónalo.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <button v-for="(info, tipo) in infoFormato" :key="tipo" type="button"
                            class="group relative flex flex-col items-start gap-1.5 rounded-xl border-2 p-3 text-left transition-all"
                            :class="tipoPlantilla === tipo
                                ? 'border-violet-600 bg-violet-50 shadow-sm dark:bg-violet-950/30'
                                : 'border-border hover:border-violet-300 hover:bg-accent/50'"
                            @click="tipoPlantilla = tipo as TipoPlantilla">
                            <div class="flex w-full items-center justify-between">
                                <component :is="info.icon" class="size-4"
                                    :class="tipoPlantilla === tipo ? 'text-violet-600' : 'text-muted-foreground'" />
                                <span v-if="tipoPlantilla === tipo"
                                    class="flex size-4 items-center justify-center rounded-full bg-violet-600 text-white">
                                    <Check class="size-2.5" />
                                </span>
                            </div>
                            <span class="text-sm font-semibold"
                                :class="tipoPlantilla === tipo ? 'text-violet-700 dark:text-violet-400' : ''">
                                {{ info.titulo }}
                            </span>
                            <span class="text-xs leading-snug text-muted-foreground">
                                {{ info.descripcion }}
                            </span>
                        </button>
                    </div>

                    <div class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-10 text-center transition-colors"
                        :class="arrastrando
                            ? 'border-violet-500 bg-violet-50 dark:bg-violet-950/30'
                            : 'border-border hover:border-violet-300 hover:bg-accent/30'" @click="abrirSelectorArchivo"
                        @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false"
                        @drop.prevent="onDrop">
                        <template v-if="subiendo">
                            <div
                                class="size-6 animate-spin rounded-full border-2 border-violet-600 border-t-transparent" />
                            <span class="text-sm font-semibold text-violet-700 dark:text-violet-400">
                                Subiendo {{ archivoSeleccionado }}…
                            </span>
                        </template>
                        <template v-else>
                            <Upload class="size-6" :class="arrastrando ? 'text-violet-600' : 'text-muted-foreground'" />
                            <span class="text-sm font-semibold text-violet-700 dark:text-violet-400">
                                {{ arrastrando ? 'Suelta el archivo aquí' : 'Arrastra tu Excel o haz clic' }}
                            </span>
                            <span class="text-xs text-muted-foreground">Formatos: .xlsx, .xls</span>
                        </template>

                        <input ref="archivoInput" type="file" accept=".xlsx,.xls" class="hidden" :disabled="subiendo"
                            @change="onInputChange" @click.stop />
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Header morado -->
        <div class="overflow-hidden rounded-2xl border shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-4 bg-gradient-to-r from-violet-600 to-purple-600 px-6 py-6 text-white">
                <div class="flex items-center gap-3">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15">
                        <Boxes class="size-6" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">Explosión de Insumos</p>
                        <p class="text-sm text-violet-100">{{ cotizacion.folio }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a :href="InsumoController.plantilla(rutaInsumos).url">
                        <Button variant="secondary" size="sm">
                            <Download class="mr-2 size-4" />
                            Descargar Plantilla
                        </Button>
                    </a>

                    <Button variant="secondary" size="sm" @click="importDialogOpen = true">
                        <Upload class="mr-2 size-4" />
                        Subir Excel
                    </Button>

                    <a :href="InsumoController.pdf(rutaInsumos).url" target="_blank">
                        <Button variant="secondary" size="sm">
                            <Download class="mr-2 size-4" />
                            Exportar PDF
                        </Button>
                    </a>

                    <Button v-if="puedeEditar" variant="outline" size="sm"
                        class="border-white/30 bg-white/10 text-white hover:bg-white/20"
                        @click="modo = modo === 'edit' ? 'view' : 'edit'">
                        <X v-if="modo === 'edit'" class="mr-2 size-4" />
                        <PencilLine v-else class="mr-2 size-4" />
                        {{ modo === 'edit' ? 'Salir de edición' : 'Editar' }}
                    </Button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 divide-x divide-y border-b bg-card sm:grid-cols-5 sm:divide-y-0">
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Total</span>
                    <span class="text-xl font-bold">{{ resumen.total }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Materiales</span>
                    <span class="text-xl font-bold text-blue-600">{{ resumen.materiales }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Mano de Obra</span>
                    <span class="text-xl font-bold text-emerald-600">{{ resumen.manoObra }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4">
                    <span class="text-xs text-muted-foreground">Maquinaria</span>
                    <span class="text-xl font-bold text-orange-600">{{ resumen.maquinaria }}</span>
                </div>
                <div class="flex flex-col gap-1 border-l-2 border-l-amber-500 p-4">
                    <span class="text-xs text-muted-foreground">Requisitados</span>
                    <span class="text-xl font-bold text-amber-600">{{ resumen.requisitados }}</span>
                </div>
            </div>
        </div>

        <!-- Resumen financiero -->
        <div class="mt-6 grid gap-4 rounded-2xl border bg-card p-6 shadow-sm sm:grid-cols-3">
            <div>
                <p class="text-xs text-muted-foreground">Subtotal Insumos</p>
                <p class="text-lg font-bold">{{ formatoMoneda(resumen.subtotal) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">IVA (16%)</p>
                <p class="text-lg font-bold">{{ formatoMoneda(resumen.iva) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Total Insumos (con IVA)</p>
                <p class="text-lg font-bold text-violet-600">{{ formatoMoneda(resumen.totalConIva) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Total Cotización</p>
                <p class="text-lg font-bold">{{ formatoMoneda(resumen.totalCotizacion) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Utilidad Estimada</p>
                <p class="text-lg font-bold"
                    :class="resumen.utilidadEstimada < 0 ? 'text-red-600' : 'text-emerald-600'">
                    {{ formatoMoneda(resumen.utilidadEstimada) }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Margen Estimado</p>
                <p class="text-lg font-bold"
                    :class="(resumen.margenEstimado ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600'">
                    {{ resumen.margenEstimado !== null ? `${resumen.margenEstimado}%` : '—' }}
                </p>
            </div>
        </div>

        <p v-if="modo === 'edit'" class="mt-6 text-sm text-muted-foreground">
            Edita los campos y sal del campo (Tab o clic afuera) para guardar automáticamente. No hay botón de guardar.
        </p>

        <!-- Tablas por categoría -->
        <div v-for="seccion in secciones" :key="seccion.categoria"
            class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="mb-4">
                <p class="text-lg font-semibold">{{ seccion.titulo }}</p>
                <p class="text-sm text-muted-foreground">{{ insumosDe(seccion.categoria).length }} insumos</p>
            </div>

            <div v-if="insumosDe(seccion.categoria).length" class="overflow-hidden rounded-xl border">
                <div class="grid gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground"
                    :class="modo === 'edit' ? 'grid-cols-[100px_1fr_70px_90px_100px_110px_28px]' : 'grid-cols-[140px_1fr_80px_90px_90px_110px_100px]'">
                    <span>CODIGO</span>
                    <span>CONCEPTO</span>
                    <span>UNIDAD</span>
                    <span class="text-right">CANTIDAD</span>
                    <span class="text-right">PRECIO</span>
                    <span class="text-right">IMPORTE</span>
                    <span v-if="modo === 'edit'" />
                    <span v-else>ESTATUS</span>
                </div>

                <div v-for="item in insumosDe(seccion.categoria)" :key="item.id"
                    class="grid items-center gap-2 border-t px-4 py-2 text-sm"
                    :class="modo === 'edit' ? 'grid-cols-[100px_1fr_70px_90px_100px_110px_28px]' : 'grid-cols-[140px_1fr_80px_90px_90px_110px_100px] py-3'">
                    <span class="truncate font-medium">{{ item.codigo }}</span>

                    <template v-if="modo === 'edit' && filas[item.id]">
                        <PermissionInput :endpoint="endpointInsumos" :accion="Accion.UPDATE"
                            v-model="filas[item.id].concepto" @blur="guardarInsumo(item)" />
                        <PermissionInput :endpoint="endpointInsumos" :accion="Accion.UPDATE"
                            v-model="filas[item.id].unidad" @blur="guardarInsumo(item)" />
                        <PermissionInput :endpoint="endpointInsumos" :accion="Accion.UPDATE" type="number" step="0.01"
                            min="0" class="text-right" v-model="filas[item.id].cantidad" @blur="guardarInsumo(item)" />
                        <PermissionInput :endpoint="endpointInsumos" :accion="Accion.UPDATE" type="number" step="0.01"
                            min="0" class="text-right" v-model="filas[item.id].precio" @blur="guardarInsumo(item)" />
                        <span class="text-right font-semibold">{{ formatoMoneda(importeLocal(item)) }}</span>
                        <span class="flex items-center justify-center">
                            <Loader2 v-if="estadoGuardado[item.id] === 'guardando'"
                                class="size-4 animate-spin text-muted-foreground" />
                            <Check v-else-if="estadoGuardado[item.id] === 'guardado'" class="size-4 text-emerald-600" />
                        </span>
                    </template>

                    <template v-else>
                        <span class="truncate text-muted-foreground">{{ item.concepto }}</span>
                        <span>{{ item.unidad }}</span>
                        <span class="text-right">{{ item.cantidad.toFixed(2) }}</span>
                        <span class="text-right text-muted-foreground">{{ item.precio !== null ?
                            formatoMoneda(item.precio) : '—' }}</span>
                        <span class="text-right font-semibold">{{ formatoMoneda(item.importe) }}</span>
                        <span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium capitalize"
                                :class="estatusBadgeClass(item.estatus)">
                                {{ item.estatus }}
                            </span>
                        </span>
                    </template>
                </div>
            </div>

            <p v-else class="rounded-xl border py-8 text-center text-sm text-muted-foreground">
                Sin insumos en esta categoría.
            </p>
        </div>
    </PageLayout>
</template>
