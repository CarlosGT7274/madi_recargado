<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, ChevronRight, ClipboardList, Plus, Send, XCircle } from '@lucide/vue';
import { computed, ref } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface ProyectoRef {
    id: number;
    nombre: string;
    folio: string;
}

interface PlantaRef {
    id: number;
    nombre: string;
}

type EstadoPlaneacion = 'borrador' | 'enviada' | 'aprobada' | 'rechazada';

interface PlaneacionResumen {
    id: number;
    semana: number;
    anio: number;
    estado: EstadoPlaneacion;
    reportadaNomina: boolean;
    proyecto: ProyectoRef | null;
    planta: PlantaRef | null;
    residente: string | null;
    aprobador: string | null;
    fechaInicio: string; // ISO yyyy-mm-dd
    fechaFin: string; // ISO yyyy-mm-dd
    fechaEnvio: string | null;
    fechaAprobacion: string | null;
    comentariosAprobacion: string | null;
}

const props = defineProps<{
    puedeCrear: boolean;
    puedeEliminar: boolean;
    puedeGestionar: boolean;
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Planeación', href: PlaneacionController.index() }],
    },
});

const estadoLabel: Record<EstadoPlaneacion, string> = {
    borrador: 'Borrador',
    enviada: 'En revisión',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const estadoBadgeClass: Record<EstadoPlaneacion, string> = {
    borrador: 'bg-muted text-muted-foreground',
    enviada: 'bg-blue-500/10 text-blue-600',
    aprobada: 'bg-emerald-500/10 text-emerald-600',
    rechazada: 'bg-red-500/10 text-red-600',
};

const puntoClase: Record<EstadoPlaneacion, string> = {
    borrador: 'bg-muted-foreground',
    enviada: 'bg-blue-500',
    aprobada: 'bg-emerald-500',
    rechazada: 'bg-red-500',
};

// ---------- Calendario anual ----------

const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];
const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const anioActual = ref(new Date().getFullYear());

function toIso(fecha: Date): string {
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
}

function celdasDeMes(anio: number, mes: number) {
    const primerDia = new Date(anio, mes, 1);
    const inicioOffset = (primerDia.getDay() + 6) % 7;
    const totalDias = new Date(anio, mes + 1, 0).getDate();

    const celdas: { fecha: Date | null; iso: string | null }[] = [];
    for (let i = 0; i < inicioOffset; i++) celdas.push({ fecha: null, iso: null });
    for (let d = 1; d <= totalDias; d++) {
        const fecha = new Date(anio, mes, d);
        celdas.push({ fecha, iso: toIso(fecha) });
    }
    return celdas;
}

const meses = computed(() =>
    nombresMeses.map((nombre, indice) => ({
        nombre,
        indice,
        celdas: celdasDeMes(anioActual.value, indice),
    })),
);

// ---------- Rango seleccionado ----------

const rangoInicio = ref<string | null>(null);
const rangoFin = ref<string | null>(null);

function seleccionarDia(iso: string): void {
    if (!rangoInicio.value || (rangoInicio.value && rangoFin.value)) {
        rangoInicio.value = iso;
        rangoFin.value = null;
        return;
    }

    if (iso >= rangoInicio.value) {
        rangoFin.value = iso;
    } else {
        rangoFin.value = rangoInicio.value;
        rangoInicio.value = iso;
    }
}

function limpiarSeleccion(): void {
    rangoInicio.value = null;
    rangoFin.value = null;
}

function diaEnRangoSeleccionado(iso: string): boolean {
    if (!rangoInicio.value) return false;
    const fin = rangoFin.value ?? rangoInicio.value;
    return iso >= rangoInicio.value && iso <= fin;
}

function estadosDelDia(lista: PlaneacionResumen[], iso: string): EstadoPlaneacion[] {
    return lista.filter((p) => iso >= p.fechaInicio && iso <= p.fechaFin).map((p) => p.estado);
}

// ---------- Lista filtrada ----------

function seOverlapaConRango(p: PlaneacionResumen): boolean {
    if (!rangoInicio.value) return true;
    const fin = rangoFin.value ?? rangoInicio.value;
    return p.fechaInicio <= fin && p.fechaFin >= rangoInicio.value;
}

function listaVisible(lista: PlaneacionResumen[]): PlaneacionResumen[] {
    return lista.filter(seOverlapaConRango);
}

// ---------- Acciones ----------

function crear(): void {
    router.visit(PlaneacionController.create().url);
}

function enviar(id: number): void {
    router.post(PlaneacionController.enviar(id).url, {}, { preserveScroll: true });
}

function eliminar(id: number): void {
    if (!confirm('¿Eliminar esta planeación? Esta acción no se puede deshacer.')) return;
    router.delete(PlaneacionController.destroy(id).url, { preserveScroll: true });
}

function aprobar(id: number): void {
    router.post(PlaneacionController.aprobar(id).url, {}, { preserveScroll: true });
}

function rechazar(id: number): void {
    const comentarios = prompt('Motivo del rechazo:');
    if (!comentarios) return;
    router.post(PlaneacionController.rechazar(id).url, { comentarios }, { preserveScroll: true });
}

function reportarNomina(id: number): void {
    router.post(PlaneacionController.reportarNomina(id).url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Planeación" />

    <PageLayout title="Planeación" description="Consulta y gestiona las planeaciones semanales">
        <template #actions>
            <Button v-if="props.puedeCrear" @click="crear">
                <Plus class="mr-2 size-4" />
                Nueva Planeación
            </Button>
        </template>

        <Deferred data="planeaciones">
            <template #fallback>
                <div class="h-96 animate-pulse rounded-2xl border bg-card/50" />
            </template>

            <div v-if="planeaciones" class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <!-- Calendario anual -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <button type="button" class="rounded-md p-1 hover:bg-accent" @click="anioActual--">
                            <ChevronLeft class="size-4" />
                        </button>
                        <p class="text-lg font-semibold">{{ anioActual }}</p>
                        <button type="button" class="rounded-md p-1 hover:bg-accent" @click="anioActual++">
                            <ChevronRight class="size-4" />
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div v-for="mes in meses" :key="mes.indice" class="rounded-xl border p-3">
                            <p class="mb-2 text-center text-sm font-semibold">{{ mes.nombre }}</p>

                            <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] text-muted-foreground">
                                <span v-for="(d, i) in diasSemana" :key="`${d}-${i}`">{{ d }}</span>
                            </div>

                            <div class="mt-0.5 grid grid-cols-7 gap-0.5">
                                <button
                                    v-for="(celda, idx) in mes.celdas"
                                    :key="idx"
                                    type="button"
                                    :disabled="!celda.fecha"
                                    class="relative flex aspect-square flex-col items-center justify-center gap-0.5 rounded text-[11px] transition-colors disabled:cursor-default"
                                    :class="[
                                        !celda.fecha ? 'invisible' : 'hover:bg-accent',
                                        celda.iso && diaEnRangoSeleccionado(celda.iso) ? 'bg-primary text-primary-foreground hover:bg-primary' : '',
                                    ]"
                                    @click="celda.iso && seleccionarDia(celda.iso)"
                                >
                                    {{ celda.fecha?.getDate() }}
                                    <span v-if="celda.iso" class="flex gap-0.5">
                                        <span
                                            v-for="(estado, i2) in estadosDelDia(planeaciones, celda.iso).slice(0, 3)"
                                            :key="i2"
                                            class="size-1 rounded-full"
                                            :class="puntoClase[estado]"
                                        />
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de planeaciones -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ rangoInicio ? 'Planeaciones en el rango seleccionado' : 'Todas las planeaciones' }}
                        </p>
                        <button v-if="rangoInicio" type="button" class="text-xs text-primary hover:underline" @click="limpiarSeleccion">
                            Limpiar
                        </button>
                    </div>
                    <p v-if="rangoInicio" class="mb-3 text-xs text-muted-foreground">
                        {{ rangoInicio }} — {{ rangoFin ?? rangoInicio }}
                    </p>

                    <div v-if="listaVisible(planeaciones).length" class="space-y-3">
                        <div v-for="p in listaVisible(planeaciones)" :key="p.id" class="rounded-xl border p-3">
                            <Link :href="PlaneacionController.show(p.id)" class="block">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold">Semana {{ p.semana }}/{{ p.anio }}</p>
                                    <Badge :class="estadoBadgeClass[p.estado]" class="text-[10px] uppercase">
                                        {{ estadoLabel[p.estado] }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ p.proyecto?.nombre ?? '—' }} · {{ p.planta?.nombre ?? '—' }}
                                </p>
                                <p v-if="props.puedeGestionar" class="text-xs text-muted-foreground">
                                    Residente: {{ p.residente ?? '—' }}
                                </p>
                            </Link>

                            <!-- Acciones del residente sobre su propia planeación -->
                            <div v-if="!props.puedeGestionar && p.estado === 'borrador'" class="mt-3 flex gap-2">
                                <Button size="sm" @click="enviar(p.id)">
                                    <Send class="mr-1.5 size-3.5" />
                                    Enviar a revisión
                                </Button>
                                <Button
                                    v-if="props.puedeEliminar"
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:bg-destructive/10"
                                    @click="eliminar(p.id)"
                                >
                                    Eliminar
                                </Button>
                            </div>
                            <p
                                v-else-if="!props.puedeGestionar && p.estado === 'rechazada' && p.comentariosAprobacion"
                                class="mt-2 rounded-lg border border-red-200 bg-red-50 px-2 py-1.5 text-xs text-red-700 dark:border-red-900 dark:bg-red-950/20 dark:text-red-400"
                            >
                                Motivo: {{ p.comentariosAprobacion }}
                            </p>

                            <!-- Acciones del que puede gestionar -->
                            <div v-if="props.puedeGestionar && p.estado === 'enviada'" class="mt-3 flex gap-2">
                                <Button size="sm" class="flex-1 bg-emerald-600 text-white hover:bg-emerald-700" @click="aprobar(p.id)">
                                    <CheckCircle2 class="mr-1.5 size-3.5" />
                                    Aprobar
                                </Button>
                                <Button size="sm" variant="outline" class="flex-1 border-red-300 text-red-600 hover:bg-red-50" @click="rechazar(p.id)">
                                    <XCircle class="mr-1.5 size-3.5" />
                                    Rechazar
                                </Button>
                            </div>
                            <Button
                                v-else-if="props.puedeGestionar && p.estado === 'aprobada' && !p.reportadaNomina"
                                size="sm"
                                variant="outline"
                                class="mt-3 w-full"
                                @click="reportarNomina(p.id)"
                            >
                                <Send class="mr-1.5 size-3.5" />
                                Reportar a nómina
                            </Button>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center gap-2 py-12 text-center text-sm text-muted-foreground">
                        <ClipboardList class="size-8" />
                        <p>{{ rangoInicio ? 'Sin planeaciones en ese rango' : 'No hay planeaciones' }}</p>
                        <Link v-if="props.puedeCrear" :href="PlaneacionController.create()">
                            <Button size="sm" class="mt-1">Crear Planeación</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </Deferred>
    </PageLayout>
</template>
