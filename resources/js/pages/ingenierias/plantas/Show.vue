<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsPlanta, type PlantaRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
}

export default pageLayout(() => breadcrumbsPlanta(usePage<Props>().props.planta));
</script>

<script setup lang="ts">
import { Deferred, Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Building2,
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    Clock,
    FolderOpen,
    Plus,
} from '@lucide/vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import PageLayout from '@/components/PageLayout.vue';
import InputError from '@/components/InputError.vue';
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

type PlantaDetalle = {
    id: number;
    folio: string;
    nombre: string;
    direccion: string | null;
    descripcion: string | null;
    activa: boolean;
    creada: string | null;
    modificada: string | null;
};

type ProyectoResumen = {
    id: number;
    folio: string;
    nombre: string;
    tipo: string;
    estado: string;
    bloqueado: boolean;
    creado: string | null;
    creado_iso: string | null;
};

const props = defineProps<{
    planta: PlantaDetalle;
    proyectos?: ProyectoResumen[];
}>();

const editDialogOpen = ref(false);

function eliminarPlanta() {
    if (!confirm(`¿Eliminar la planta "${props.planta.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(PlantaController.destroy(props.planta.id).url);
}

const estatusLabel: Record<string, string> = {
    activo: 'Activo',
    terminado: 'Terminado',
    cancelado: 'Cancelado',
};

const estatusGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    activo: 'pendiente',
    terminado: 'aprobado',
    cancelado: 'negativo',
};

function estatusBadgeClass(estado: string) {
    const grupo = estatusGrupo[estado] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function tipoVariant(tipo: string) {
    if (tipo === 'grande') return 'default';
    return 'secondary';
}

function tipoLabel(tipo: string) {
    if (tipo === 'grande') return 'Con Levantamiento';
    if (tipo === 'chico') return 'Directo a Actividades';
    return tipo;
}

// --- Calendario ---
const hoy = new Date();
const mesActual = ref(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
const fechaSeleccionada = ref<string | null>(null);

function toIso(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const diasConProyectos = computed(() => {
    const set = new Set<string>();
    for (const pry of props.proyectos ?? []) {
        if (pry.creado_iso) set.add(pry.creado_iso);
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

const proyectosFiltrados = computed(() => {
    const lista = props.proyectos ?? [];
    if (!fechaSeleccionada.value) return lista;
    return lista.filter((l) => l.creado_iso === fechaSeleccionada.value);
});

const totales = computed(() => {
    const lista = proyectosFiltrados.value;
    const terminados = lista.filter((l) => estatusGrupo[l.estado] === 'aprobado').length;
    const activos = lista.filter((l) => (estatusGrupo[l.estado] ?? 'pendiente') === 'pendiente').length;
    return { terminados, activos, total: lista.length };
});

const nombreMes = computed(() =>
    mesActual.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }),
);

const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
</script>

<template>
    <Head :title="`Planta: ${planta.nombre}`" />

    <PageLayout
        :title="planta.nombre"
        :description="`Folio ${planta.folio}`"
        endpoint="ingenierias.plantas"
        with-edit
        with-delete
        @edit="editDialogOpen = true"
        @delete="eliminarPlanta"
    >
        <!-- Dialog: editar planta -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <Form
                    v-bind="PlantaController.update.form(planta.id)"
                    :options="{ preserveScroll: true }"
                    @success="editDialogOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>Editar planta</DialogTitle>
                        <DialogDescription>Actualiza los datos de esta planta.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit-folio">Folio</Label>
                        <Input id="edit-folio" name="folio" :default-value="planta.folio" />
                        <InputError :message="errors.folio" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-nombre">Nombre</Label>
                        <Input id="edit-nombre" name="nombre" :default-value="planta.nombre" />
                        <InputError :message="errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-direccion">Dirección</Label>
                        <Input id="edit-direccion" name="direccion" :default-value="planta.direccion ?? ''" />
                        <InputError :message="errors.direccion" />
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
            <!-- Header: planta + acción nuevo proyecto -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b bg-muted/30 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Building2 class="size-6" />
                        </div>
                        <div>
                            <p class="text-lg font-semibold leading-tight">{{ planta.nombre }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ planta.folio }} · Creada: {{ planta.creada ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <Link :href="ProyectoController.create(planta.id)">
                        <Button>
                            <Plus class="size-4" />
                            Nuevo Proyecto
                        </Button>
                    </Link>
                </div>

                <Deferred data="proyectos">
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
                                {{ totales.terminados }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Terminados</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="flex items-center gap-1 text-2xl font-bold text-amber-600">
                                <Clock class="size-5" />
                                {{ totales.activos }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Activos</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="text-2xl font-bold">{{ totales.total }}</span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Total</span>
                        </div>
                    </div>
                </Deferred>
            </div>

            <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                <!-- Calendario -->
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
                        <button
                            v-for="(celda, idx) in diasDelMes"
                            :key="idx"
                            type="button"
                            :disabled="!celda.fecha"
                            class="relative flex aspect-square items-center justify-center rounded-md text-sm transition-colors disabled:cursor-default"
                            :class="[
                                !celda.fecha ? 'invisible' : 'hover:bg-accent',
                                fechaSeleccionada === celda.iso ? 'bg-primary text-primary-foreground hover:bg-primary' : '',
                            ]"
                            @click="seleccionarDia(celda.iso)"
                        >
                            {{ celda.fecha?.getDate() }}
                            <span
                                v-if="celda.iso && diasConProyectos.has(celda.iso) && fechaSeleccionada !== celda.iso"
                                class="absolute bottom-1 size-1 rounded-full bg-primary"
                            />
                        </button>
                    </div>

                    <button
                        v-if="fechaSeleccionada"
                        type="button"
                        class="mt-3 w-full rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                        @click="fechaSeleccionada = null"
                    >
                        Ver todos los proyectos
                    </button>
                </div>

                <!-- Lista filtrada -->
                <Deferred data="proyectos">
                    <template #fallback>
                        <div class="flex flex-col items-center gap-3 rounded-2xl border bg-card py-12 text-center shadow-sm">
                            <FolderOpen class="size-8 text-muted-foreground" />
                            <p class="text-sm font-medium text-muted-foreground">Cargando proyectos…</p>
                        </div>
                    </template>

                    <div class="space-y-3">
                        <Link
                            v-for="pry in proyectosFiltrados"
                            :key="pry.id"
                            :href="ProyectoController.show([planta.id, pry.id])"
                            class="flex items-start gap-4 rounded-2xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
                        >
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                <FolderOpen class="size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ pry.folio }}</p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[11px] font-medium uppercase"
                                        :class="estatusBadgeClass(pry.estado)"
                                    >
                                        {{ estatusLabel[pry.estado] ?? pry.estado }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm">{{ pry.nombre ?? '—' }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <Badge :variant="tipoVariant(pry.tipo)" class="text-[10px]">
                                            {{ tipoLabel(pry.tipo) }}
                                        </Badge>
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">Creado: {{ pry.creado ?? '—' }}</p>
                            </div>
                        </Link>

                        <div
                            v-if="!proyectosFiltrados.length"
                            class="flex flex-col items-center gap-3 rounded-2xl border bg-card py-12 text-center shadow-sm"
                        >
                            <FolderOpen class="size-8 text-muted-foreground" />
                            <p class="text-sm font-medium">No hay proyectos para esta fecha</p>
                            <button
                                v-if="fechaSeleccionada"
                                type="button"
                                class="rounded-md bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                                @click="fechaSeleccionada = null"
                            >
                                Ver todos
                            </button>
                        </div>
                    </div>
                </Deferred>
            </div>
        </div>
    </PageLayout>
</template>
