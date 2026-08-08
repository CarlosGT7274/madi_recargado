<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsProyecto, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
}

export default pageLayout(() => {
    const { planta, proyecto } = usePage<Props>().props;
    return breadcrumbsProyecto(planta, proyecto);
});
</script>

<script setup lang="ts">
import { Deferred, Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    ClipboardList,
    Clock,
    FileSpreadsheet,
    FileText,
    Plus,
    ShoppingCart,
    Upload,
} from '@lucide/vue';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
import InputError from '@/components/InputError.vue';
import ActividadesArbol, { type ActividadNodo } from '@/components/ActividadesArbol.vue';
import { Badge } from '@/components/ui/badge';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ChevronDown } from '@lucide/vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import CotizacionManualForm from './components/CotizacionManualForm.vue';

type PlantaRef = { id: number; nombre: string };

type ProyectoDetalle = {
    id: number;
    planta_id: number;
    folio: string;
    tipo: string;
    nombre: string;
    descripcion: string | null;
    estado: string;
    completado: boolean;
    creado: string | null;
};

type LevantamientoResumen = {
    id: number;
    folio: string;
    nombre: string;
    cliente: string;
    prioridad: string;
    estatus_admin: string;
    creado: string | null;
    creado_iso: string | null;
};

type ObraAgrupada = {
    obra: string;
    totalVersiones: number;
    completada: boolean;
    ultimaVersion: {
        id: number;
        folio: string;
        total: number | null;
        completada: boolean;
        tieneAutorizacion: boolean;
    };
};

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoDetalle;
    levantamientos?: LevantamientoResumen[];
    obras?: ObraAgrupada[];
    actividades?: ActividadNodo[];
}>();

const editDialogOpen = ref(false);

function eliminarProyecto() {
    if (!confirm(`¿Eliminar el proyecto "${props.proyecto.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(ProyectoController.destroy([props.planta.id, props.proyecto.id]).url);
}

const prioridadLabel: Record<string, string> = {
    urgente: 'Urgente',
    normal: 'Normal',
    grande_compleja: 'Grande / Compleja',
};

const estatusLabel: Record<string, string> = {
    recibida: 'Recibida',
    levantamiento_proceso: 'En proceso',
    levantamiento_listo: 'Listo',
    cotizando: 'Cotizando',
    revision_residente: 'Revisión',
    correcciones: 'Correcciones',
    lista_enviar: 'Lista enviar',
    enviada: 'Enviada',
    ganada: 'Ganada',
    perdida: 'Perdida',
    cancelada: 'Cancelada',
};

const estatusGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    recibida: 'pendiente',
    levantamiento_proceso: 'pendiente',
    levantamiento_listo: 'pendiente',
    cotizando: 'pendiente',
    revision_residente: 'pendiente',
    correcciones: 'pendiente',
    lista_enviar: 'pendiente',
    enviada: 'pendiente',
    ganada: 'aprobado',
    perdida: 'negativo',
    cancelada: 'negativo',
};

function estatusBadgeClass(estatus: string) {
    const grupo = estatusGrupo[estatus] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function prioridadVariant(prioridad: string) {
    if (prioridad === 'urgente') return 'destructive';
    if (prioridad === 'grande_compleja') return 'outline';
    return 'secondary';
}

function tipoLabel(tipo: string) {
    if (tipo === 'grande') return 'Proyecto estándar';
    if (tipo === 'chico') return 'Proyecto directo';
    return tipo;
}

// --- Calendario (solo Proyecto estándar) ---
const hoy = new Date();
const mesActual = ref(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
const fechaSeleccionada = ref<string | null>(null);

function toIso(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const diasConLevantamientos = computed(() => {
    const set = new Set<string>();
    for (const lev of props.levantamientos ?? []) {
        if (lev.creado_iso) set.add(lev.creado_iso);
    }
    return set;
});

const diasDelMes = computed(() => {
    const year = mesActual.value.getFullYear();
    const month = mesActual.value.getMonth();
    const primerDia = new Date(year, month, 1);
    const inicioOffset = (primerDia.getDay() + 6) % 7;
    const totalDias = new Date(year, month + 1, 0).getDate();

    const celdas: { fecha: Date | null; iso: string | null }[] = [];
    for (let i = 0; i < inicioOffset; i++) {
        celdas.push({ fecha: null, iso: null });
    }
    for (let d = 1; d <= totalDias; d++) {
        const fecha = new Date(year, month, d);
        celdas.push({ fecha, iso: toIso(fecha) });
    }
    return celdas;
});

function cambiarMes(delta: number) {
    mesActual.value = new Date(mesActual.value.getFullYear(), mesActual.value.getMonth() + delta, 1);
}

function seleccionarDia(iso: string | null) {
    fechaSeleccionada.value = fechaSeleccionada.value === iso ? null : iso;
}

const levantamientosFiltrados = computed(() => {
    const lista = props.levantamientos ?? [];
    if (!fechaSeleccionada.value) return lista;
    return lista.filter((l) => l.creado_iso === fechaSeleccionada.value);
});

const totales = computed(() => {
    const lista = levantamientosFiltrados.value;
    const aprobados = lista.filter((l) => estatusGrupo[l.estatus_admin] === 'aprobado').length;
    const pendientes = lista.filter((l) => (estatusGrupo[l.estatus_admin] ?? 'pendiente') === 'pendiente').length;
    return { aprobados, pendientes, total: lista.length };
});

const nombreMes = computed(() =>
    mesActual.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }),
);

const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

// --- Proyecto directo: subir cotización ---
const archivoCotizacionInput = ref<HTMLInputElement | null>(null);

const captureManualOpen = ref(false);

function subirCotizacionExcel(): void {
    const archivo = archivoCotizacionInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        CotizacionController.storeProyecto({ planta: props.planta.id, proyecto: props.proyecto.id }).url,
        { archivo },
        { forceFormData: true, preserveScroll: true, onFinish: () => { if (archivoCotizacionInput.value) archivoCotizacionInput.value.value = ''; } },
    );
}

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>

    <Head :title="`Proyecto: ${proyecto.nombre}`" />

    <PageLayout :title="proyecto.nombre" :description="`Folio ${proyecto.folio}`"
        endpoint="ingenierias.plantas.proyectos" with-edit with-delete @edit="editDialogOpen = true"
        @delete="eliminarProyecto">
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <Form v-bind="ProyectoController.update.form([planta.id, proyecto.id])"
                    :options="{ preserveScroll: true }" @success="editDialogOpen = false"
                    v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Editar proyecto</DialogTitle>
                        <DialogDescription>Actualiza los datos de este proyecto.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit-nombre">Nombre</Label>
                        <Input id="edit-nombre" name="nombre" :default-value="proyecto.nombre" />
                        <InputError :message="errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-tipo">Tipo</Label>
                        <Input id="edit-tipo" name="tipo" :default-value="tipoLabel(proyecto.tipo)" readonly />
                        <InputError :message="errors.tipo" />
                        <p class="text-xs text-muted-foreground">El tipo no puede cambiar una vez creado.</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-descripcion">Descripción</Label>
                        <Input id="edit-descripcion" name="descripcion" :default-value="proyecto.descripcion ?? ''" />
                        <InputError :message="errors.descripcion" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar cambios</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="space-y-6">
            <!-- Header: proyecto + acción principal según tipo -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b bg-muted/30 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <FileText class="size-6" />
                        </div>
                        <div>
                            <p class="text-lg font-semibold leading-tight">{{ proyecto.nombre }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ proyecto.folio }} · {{ tipoLabel(proyecto.tipo) }} · Creado: {{ proyecto.creado ??
                                    '—' }}
                            </p>
                        </div>
                    </div>

                    <Link v-if="proyecto.tipo === 'grande'"
                        :href="LevantamientoController.create({ planta: planta.id, proyecto: proyecto.id })">
                        <Button>
                            <Plus class="size-4" />
                            Nuevo Levantamiento
                        </Button>
                    </Link>

                    <div v-else class="flex flex-col items-end gap-2">
                        <div class="flex items-center gap-2">
                            <label>
                                <Button as="span" class="cursor-pointer">
                                    <Upload class="size-4" />
                                    Subir cotización
                                </Button>
                                <input ref="archivoCotizacionInput" type="file" accept=".xlsx,.xls" class="hidden"
                                    @change="subirCotizacionExcel" />
                            </label>

                            <Collapsible v-model:open="captureManualOpen">
                                <CollapsibleTrigger as-child>
                                    <Button variant="outline">
                                        <ChevronDown class="size-4 transition-transform"
                                            :class="captureManualOpen ? 'rotate-180' : ''" />
                                        Crear manual
                                    </Button>
                                </CollapsibleTrigger>
                            </Collapsible>
                        </div>
                    </div>
                </div>

                <Deferred data="levantamientos" v-if="proyecto.tipo === 'grande'">
                    <template #fallback>
                        <div class="grid grid-cols-3 divide-x border-b py-6 text-center text-sm text-muted-foreground">
                            <div>Cargando…</div>
                            <div>Cargando…</div>
                            <div>Cargando…</div>
                        </div>
                    </template>

                    <div class="grid grid-cols-3 divide-x border-b">
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="flex items-center gap-1 text-2xl font-bold text-emerald-600">
                                <CircleCheck class="size-5" />
                                {{ totales.aprobados }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Aprobados</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="flex items-center gap-1 text-2xl font-bold text-amber-600">
                                <Clock class="size-5" />
                                {{ totales.pendientes }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Pendientes</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="text-2xl font-bold">{{ totales.total }}</span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Total</span>
                        </div>
                    </div>
                </Deferred>
            </div>
            <Collapsible v-if="proyecto.tipo === 'chico'" v-model:open="captureManualOpen">
                <CollapsibleContent>
                    <div class="border-t px-6 py-5">
                        <CotizacionManualForm :planta="planta" :proyecto="proyecto"
                            @success="captureManualOpen = false" />
                    </div>
                </CollapsibleContent>
            </Collapsible>
            <!-- Proyecto estándar: calendario + lista de levantamientos -->
            <div v-if="proyecto.tipo === 'grande'" class="grid gap-6 lg:grid-cols-[320px_1fr]">
                <div class="h-fit rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarMes(-1)">
                            <ChevronLeft class="size-4" />
                        </button>
                        <p class="text-sm font-medium capitalize">{{ nombreMes }}</p>
                        <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarMes(1)">
                            <ChevronRight class="size-4" />
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground">
                        <span v-for="d in diasSemana" :key="d">{{ d }}</span>
                    </div>

                    <div class="mt-1 grid grid-cols-7 gap-1">
                        <button v-for="(celda, idx) in diasDelMes" :key="idx" type="button" :disabled="!celda.fecha"
                            class="relative flex aspect-square items-center justify-center rounded-md text-sm transition-colors disabled:cursor-default"
                            :class="[
                                !celda.fecha ? 'invisible' : 'hover:bg-accent',
                                fechaSeleccionada === celda.iso ? 'bg-primary text-primary-foreground hover:bg-primary' : '',
                            ]" @click="seleccionarDia(celda.iso)">
                            {{ celda.fecha?.getDate() }}
                            <span
                                v-if="celda.iso && diasConLevantamientos.has(celda.iso) && fechaSeleccionada !== celda.iso"
                                class="absolute bottom-1 size-1 rounded-full bg-primary" />
                        </button>
                    </div>

                    <button v-if="fechaSeleccionada" type="button"
                        class="mt-3 w-full rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                        @click="fechaSeleccionada = null">
                        Ver todos los levantamientos
                    </button>
                </div>

                <Deferred data="levantamientos">
                    <template #fallback>
                        <div
                            class="flex flex-col items-center gap-3 rounded-2xl border bg-card py-12 text-center shadow-sm">
                            <FileText class="size-8 text-muted-foreground" />
                            <p class="text-sm font-medium text-muted-foreground">Cargando levantamientos…</p>
                        </div>
                    </template>

                    <div class="space-y-3">
                        <Link v-for="lev in levantamientosFiltrados" :key="lev.id"
                            :href="LevantamientoController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: lev.id })"
                            class="flex items-start gap-4 rounded-2xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                <FileText class="size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ lev.folio }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium uppercase"
                                        :class="estatusBadgeClass(lev.estatus_admin)">
                                        {{ estatusLabel[lev.estatus_admin] ?? lev.estatus_admin }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm">{{ lev.nombre ?? '—' }}</p>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                    <span>Cliente: {{ lev.cliente ?? '—' }}</span>
                                    <span class="flex items-center gap-1">
                                        <Badge :variant="prioridadVariant(lev.prioridad)" class="text-[10px]">
                                            {{ prioridadLabel[lev.prioridad] ?? lev.prioridad }}
                                        </Badge>
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">Creado: {{ lev.creado ?? '—' }}</p>
                            </div>
                        </Link>

                        <div v-if="!levantamientosFiltrados.length"
                            class="flex flex-col items-center gap-3 rounded-2xl border bg-card py-12 text-center shadow-sm">
                            <FileText class="size-8 text-muted-foreground" />
                            <p class="text-sm font-medium">No hay levantamientos para esta fecha</p>
                            <button v-if="fechaSeleccionada" type="button"
                                class="rounded-md bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                                @click="fechaSeleccionada = null">
                                Ver todos
                            </button>
                        </div>
                    </div>
                </Deferred>
            </div>

            <!-- Proyecto directo: cotización + OC (obras) + actividades -->
            <div v-else class="space-y-6">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <FileSpreadsheet class="size-4" />
                        Cotizaciones
                    </div>

                    <Deferred data="obras">
                        <template #fallback>
                            <p class="py-6 text-center text-sm text-muted-foreground">Cargando cotizaciones…</p>
                        </template>

                        <div v-if="obras?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <Link v-for="grupo in obras" :key="grupo.obra" :href="CotizacionController.obraProyecto({
                                planta: planta.id,
                                proyecto: proyecto.id,
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
                                    <span>{{ grupo.totalVersiones }} {{ grupo.totalVersiones === 1 ? 'versión' :
                                        'versiones' }}</span>
                                </div>
                            </Link>
                        </div>

                        <p v-else class="py-6 text-center text-sm text-muted-foreground">
                            Aún no hay cotizaciones. Sube el Excel para crear la primera.
                        </p>
                    </Deferred>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <ClipboardList class="size-4" />
                        Actividades
                    </div>

                    <Deferred data="actividades">
                        <template #fallback>
                            <p class="py-6 text-center text-sm text-muted-foreground">Cargando actividades…</p>
                        </template>

                        <ActividadesArbol :planta="planta.id" :proyecto="proyecto.id"
                            :actividades="actividades ?? []" />
                    </Deferred>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
