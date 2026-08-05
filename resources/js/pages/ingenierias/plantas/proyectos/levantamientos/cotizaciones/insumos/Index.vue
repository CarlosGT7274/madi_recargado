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
import { Boxes, Download, PencilLine, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import InsumoController from '@/actions/App/Http/Controllers/Ingenierias/InsumoController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';

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

const rutaInsumos = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
    levantamiento: props.levantamiento.id,
    cotizacion: props.cotizacion.id,
}));

const archivoInput = ref<HTMLInputElement | null>(null);

function subirExcel(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        InsumoController.importar(rutaInsumos.value).url,
        { archivo, tipo_plantilla: 'propia' },
        { forceFormData: true, preserveScroll: true },
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

                    <label class="cursor-pointer">
                        <Button variant="secondary" size="sm" as="span">
                            <Upload class="mr-2 size-4" />
                            Subir Excel
                        </Button>
                        <input ref="archivoInput" type="file" accept=".xlsx,.xls" class="hidden" @change="subirExcel" />
                    </label>

                    <Button variant="outline" size="sm" class="border-white/30 bg-white/10 text-white" disabled
                        title="Próximamente">
                        <Download class="mr-2 size-4" />
                        Exportar
                    </Button>

                    <Button variant="outline" size="sm" class="border-white/30 bg-white/10 text-white" disabled
                        title="Próximamente">
                        <PencilLine class="mr-2 size-4" />
                        Editar
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

        <!-- Tablas por categoría -->
        <div v-for="seccion in secciones" :key="seccion.categoria"
            class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="mb-4">
                <p class="text-lg font-semibold">{{ seccion.titulo }}</p>
                <p class="text-sm text-muted-foreground">{{ insumosDe(seccion.categoria).length }} insumos</p>
            </div>

            <div v-if="insumosDe(seccion.categoria).length" class="overflow-hidden rounded-xl border">
                <div
                    class="grid grid-cols-[140px_1fr_80px_90px_90px_110px_100px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                    <span>CODIGO</span>
                    <span>CONCEPTO</span>
                    <span>UNIDAD</span>
                    <span class="text-right">CANTIDAD</span>
                    <span class="text-right">PRECIO</span>
                    <span class="text-right">IMPORTE</span>
                    <span>ESTATUS</span>
                </div>

                <div v-for="item in insumosDe(seccion.categoria)" :key="item.id"
                    class="grid grid-cols-[140px_1fr_80px_90px_90px_110px_100px] items-center gap-2 border-t px-4 py-3 text-sm">
                    <span class="truncate font-medium">{{ item.codigo }}</span>
                    <span class="truncate text-muted-foreground">{{ item.concepto }}</span>
                    <span>{{ item.unidad }}</span>
                    <span class="text-right">{{ item.cantidad.toFixed(2) }}</span>
                    <span class="text-right text-muted-foreground">{{ item.precio !== null ? formatoMoneda(item.precio)
                        : '—' }}</span>
                    <span class="text-right font-semibold">{{ formatoMoneda(item.importe) }}</span>
                    <span>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium capitalize"
                            :class="estatusBadgeClass(item.estatus)">
                            {{ item.estatus }}
                        </span>
                    </span>
                </div>
            </div>

            <p v-else class="rounded-xl border py-8 text-center text-sm text-muted-foreground">
                Sin insumos en esta categoría.
            </p>
        </div>
    </PageLayout>
</template>
