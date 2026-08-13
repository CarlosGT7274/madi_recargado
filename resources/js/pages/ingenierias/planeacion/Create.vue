<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Box, Boxes, CalendarDays, CheckCircle2, ChevronLeft, ChevronRight, FileText, Layers } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { diasDeSemana, isoWeekInfo, lunesDeSemanaIso } from '@/lib/isoWeek';

interface ProyectoOpcion {
    id: number;
    nombre: string;
    folio: string;
    tipo: string;
}

interface PlantaOpcion {
    id: number;
    nombre: string;
    folio: string;
    proyectos: ProyectoOpcion[];
}

interface CotizacionAprobada {
    id: number;
    folio: string;
    obra: string | null;
    fecha: string | null;
    total: number;
}

interface PartidaDisponible {
    id: number;
    descripcion: string;
    unidad: string | null;
    cantidad: number;
}

const props = defineProps<{
    plantas: PlantaOpcion[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Planeación', href: PlaneacionController.index() },
            { title: 'Nueva Planeación', href: '' },
        ],
    },
});

// ---------- Paso 1: Semana (calendario navegable, no dropdown) ----------

const diasSemanaLabel = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

/** Semana ISO actualmente elegida — todo lo demás (label, chips, grid) se deriva de esto. */
const semanaSeleccionada = ref<{ anio: number; semana: number }>(isoWeekInfo(new Date()));

/** Mes que muestra el mini-calendario; arranca en el mes de la semana seleccionada. */
const mesCalendario = ref(lunesDeSemanaIso(semanaSeleccionada.value.anio, semanaSeleccionada.value.semana));

const diasDeLaSemanaSeleccionada = computed(() =>
    diasDeSemana(semanaSeleccionada.value.anio, semanaSeleccionada.value.semana),
);

function formatoFecha(fecha: Date): string {
    return `${fecha.getDate()}/${fecha.getMonth() + 1}/${fecha.getFullYear()}`;
}

function seleccionarSemanaDeFecha(fecha: Date): void {
    semanaSeleccionada.value = isoWeekInfo(fecha);
}

function cambiarMesCalendario(delta: number): void {
    mesCalendario.value = new Date(mesCalendario.value.getFullYear(), mesCalendario.value.getMonth() + delta, 1);
}

function cambiarSemana(delta: number): void {
    const nuevoLunes = new Date(diasDeLaSemanaSeleccionada.value[0]);
    nuevoLunes.setDate(nuevoLunes.getDate() + delta * 7);
    seleccionarSemanaDeFecha(nuevoLunes);
    mesCalendario.value = new Date(nuevoLunes.getFullYear(), nuevoLunes.getMonth(), 1);
}

function irASemanaActual(): void {
    seleccionarSemanaDeFecha(new Date());
    mesCalendario.value = new Date();
}

interface CeldaCalendario {
    fecha: Date | null;
}

const celdasCalendario = computed<CeldaCalendario[]>(() => {
    const anio = mesCalendario.value.getFullYear();
    const mes = mesCalendario.value.getMonth();
    const primerDia = new Date(anio, mes, 1);
    const inicioOffset = (primerDia.getDay() + 6) % 7;
    const totalDias = new Date(anio, mes + 1, 0).getDate();

    const celdas: CeldaCalendario[] = [];
    for (let i = 0; i < inicioOffset; i++) celdas.push({ fecha: null });
    for (let d = 1; d <= totalDias; d++) celdas.push({ fecha: new Date(anio, mes, d) });
    return celdas;
});

function perteneceASemanaSeleccionada(fecha: Date | null): boolean {
    if (!fecha) return false;
    const info = isoWeekInfo(fecha);
    return info.anio === semanaSeleccionada.value.anio && info.semana === semanaSeleccionada.value.semana;
}

function esHoy(fecha: Date | null): boolean {
    if (!fecha) return false;
    const hoy = new Date();
    return fecha.getFullYear() === hoy.getFullYear() && fecha.getMonth() === hoy.getMonth() && fecha.getDate() === hoy.getDate();
}

// ---------- Paso 2: Planta / Proyecto ----------

const plantaId = ref<number | null>(null);
const proyectoId = ref<number | null>(null);

const proyectosDePlanta = computed<ProyectoOpcion[]>(
    () => props.plantas.find((p) => p.id === plantaId.value)?.proyectos ?? [],
);

watch(plantaId, () => {
    proyectoId.value = null;
});

// ---------- Paso 3: Cotización aprobada del proyecto seleccionado ----------

const cotizaciones = ref<CotizacionAprobada[]>([]);
const cotizacionId = ref<number | null>(null);
const cargandoCotizaciones = ref(false);

watch(proyectoId, async (nuevoId) => {
    cotizacionId.value = null;
    cotizaciones.value = [];
    partidas.value = [];

    if (plantaId.value === null || nuevoId === null) return;

    cargandoCotizaciones.value = true;

    try {
        const respuesta = await fetch(
            PlaneacionController.cotizacionesAprobadas.url({ planta: plantaId.value, proyecto: nuevoId }),
        );
        cotizaciones.value = (await respuesta.json()) as CotizacionAprobada[];
    } finally {
        cargandoCotizaciones.value = false;
    }
});

// ---------- Paso 4: Partidas de la cotización elegida (actividades disponibles) ----------

const partidas = ref<PartidaDisponible[]>([]);
const cargandoPartidas = ref(false);

watch(cotizacionId, async (nuevoId) => {
    partidas.value = [];

    if (plantaId.value === null || proyectoId.value === null || nuevoId === null) return;

    cargandoPartidas.value = true;

    try {
        const respuesta = await fetch(
            PlaneacionController.partidasDeCotizacion.url({
                planta: plantaId.value,
                proyecto: proyectoId.value,
                cotizacion: nuevoId,
            }),
        );
        partidas.value = (await respuesta.json()) as PartidaDisponible[];
    } finally {
        cargandoPartidas.value = false;
    }
});

// ---------- Preselección desde query string (viene del "+" en Planificador) ----------

onMounted(() => {
    const query = new URLSearchParams(window.location.search);
    const anio = Number(query.get('anio'));
    const semana = Number(query.get('semana'));

    if (anio && semana) {
        semanaSeleccionada.value = { anio, semana };
        mesCalendario.value = lunesDeSemanaIso(anio, semana);
    }

    const plantaQuery = Number(query.get('planta'));
    if (plantaQuery && props.plantas.some((p) => p.id === plantaQuery)) {
        plantaId.value = plantaQuery;

        const proyectoQuery = Number(query.get('proyecto'));
        if (proyectoQuery && proyectosDePlanta.value.some((p) => p.id === proyectoQuery)) {
            proyectoId.value = proyectoQuery;
        }
    }
});

// ---------- Helpers de UI ----------

function formatoMoneda(valor: number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}

const cotizacionSeleccionada = computed(
    () => cotizaciones.value.find((c) => c.id === cotizacionId.value) ?? null,
);

const pasoProyectoHabilitado = computed(() => plantaId.value !== null);
const pasoCotizacionHabilitado = computed(() => proyectoId.value !== null);
const pasoPartidasHabilitado = computed(() => cotizacionId.value !== null);
</script>

<template>

    <Head title="Nueva Planeación Semanal" />

    <PageLayout title="Nueva Planeación Semanal"
        description="Selecciona la semana, el proyecto y la cotización aprobada para cargar las actividades disponibles">
        <template #breadcrumbs>
            <Link :href="PlaneacionController.index()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <div class="mx-auto max-w-3xl space-y-4">
            <!-- Paso 1: Semana -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                        <CalendarDays class="size-4 text-muted-foreground" />
                        <p class="text-sm font-semibold">Semana de trabajo</p>
                    </div>
                    <button type="button" class="text-xs text-primary hover:underline" @click="irASemanaActual">
                        Semana actual
                    </button>
                </div>

                <div class="grid gap-4 sm:grid-cols-[220px_1fr]">
                    <!-- Mini-calendario mensual: clic en cualquier día selecciona su semana completa -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <button type="button" class="rounded-md p-1 hover:bg-accent"
                                @click="cambiarMesCalendario(-1)">
                                <ChevronLeft class="size-4" />
                            </button>
                            <p class="text-xs font-medium capitalize">
                                {{ nombresMeses[mesCalendario.getMonth()] }} {{ mesCalendario.getFullYear() }}
                            </p>
                            <button type="button" class="rounded-md p-1 hover:bg-accent"
                                @click="cambiarMesCalendario(1)">
                                <ChevronRight class="size-4" />
                            </button>
                        </div>

                        <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] text-muted-foreground">
                            <span v-for="d in diasSemanaLabel" :key="d">{{ d[0] }}</span>
                        </div>

                        <div class="mt-0.5 grid grid-cols-7 gap-0.5">
                            <button v-for="(celda, idx) in celdasCalendario" :key="idx" type="button"
                                :disabled="!celda.fecha"
                                class="relative flex aspect-square items-center justify-center rounded text-xs transition-colors disabled:cursor-default"
                                :class="[
                                    !celda.fecha ? 'invisible' : '',
                                    celda.fecha && !perteneceASemanaSeleccionada(celda.fecha) ? 'hover:bg-accent' : '',
                                    perteneceASemanaSeleccionada(celda.fecha) ? 'bg-primary/15' : '',
                                ]" @click="celda.fecha && seleccionarSemanaDeFecha(celda.fecha)">
                                {{ celda.fecha?.getDate() }}
                                <span v-if="esHoy(celda.fecha)"
                                    class="absolute inset-x-1.5 bottom-0.5 h-0.5 rounded-full bg-primary" />
                            </button>
                        </div>
                    </div>

                    <!-- Semana elegida: rango + los 7 días que la componen -->
                    <div class="flex flex-col justify-center gap-3 rounded-xl border bg-muted/30 p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarSemana(-1)"
                                    title="Semana anterior">
                                    <ChevronLeft class="size-3.5" />
                                </button>
                                <p class="text-sm font-semibold">
                                    Semana {{ semanaSeleccionada.semana }} — {{ semanaSeleccionada.anio }}
                                </p>
                                <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarSemana(1)"
                                    title="Semana siguiente">
                                    <ChevronRight class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            {{ formatoFecha(diasDeLaSemanaSeleccionada[0]) }} —
                            {{ formatoFecha(diasDeLaSemanaSeleccionada[6]) }}
                        </p>

                        <div class="grid grid-cols-7 gap-1">
                            <div v-for="(dia, i) in diasDeLaSemanaSeleccionada" :key="i"
                                class="flex flex-col items-center gap-0.5 rounded-lg py-1.5 text-center"
                                :class="esHoy(dia) ? 'bg-primary text-primary-foreground' : 'bg-background'">
                                <span class="text-[9px] uppercase opacity-70">{{ diasSemanaLabel[i] }}</span>
                                <span class="text-sm font-semibold">{{ dia.getDate() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Planta / Proyecto -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                    <Layers class="size-4 text-muted-foreground" />
                    <p class="text-sm font-semibold">Planta y proyecto</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <label class="text-xs font-medium text-muted-foreground">Planta</label>
                        <Select :model-value="plantaId ? String(plantaId) : undefined"
                            @update:model-value="(v) => (plantaId = v ? Number(v) : null)">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona una planta" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="p in plantas" :key="p.id" :value="String(p.id)">
                                    {{ p.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <p v-if="!plantas.length" class="text-xs text-muted-foreground">
                            No tienes plantas asignadas.
                        </p>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-medium text-muted-foreground">Proyecto</label>
                        <Select :model-value="proyectoId ? String(proyectoId) : undefined"
                            :disabled="!pasoProyectoHabilitado"
                            @update:model-value="(v) => (proyectoId = v ? Number(v) : null)">
                            <SelectTrigger>
                                <SelectValue
                                    :placeholder="pasoProyectoHabilitado ? 'Selecciona un proyecto' : 'Primero selecciona una planta'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="p in proyectosDePlanta" :key="p.id" :value="String(p.id)">
                                    {{ p.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <p v-if="pasoProyectoHabilitado && !proyectosDePlanta.length"
                            class="text-xs text-muted-foreground">
                            Esta planta no tiene proyectos.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Paso 3: Cotización aprobada -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm transition-opacity"
                :class="!pasoCotizacionHabilitado ? 'opacity-50' : ''">
                <div class="mb-3 flex items-center gap-2">
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                    <FileText class="size-4 text-muted-foreground" />
                    <p class="text-sm font-semibold">Cotización aprobada</p>
                </div>

                <Select :model-value="cotizacionId ? String(cotizacionId) : undefined"
                    :disabled="!pasoCotizacionHabilitado || cargandoCotizaciones"
                    @update:model-value="(v) => (cotizacionId = v ? Number(v) : null)">
                    <SelectTrigger class="w-full sm:w-96">
                        <SelectValue :placeholder="!pasoCotizacionHabilitado
                            ? 'Primero selecciona un proyecto'
                            : cargandoCotizaciones
                                ? 'Cargando cotizaciones…'
                                : 'Selecciona una cotización aprobada'
                            " />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="c in cotizaciones" :key="c.id" :value="String(c.id)">
                            {{ c.folio }} — {{ c.obra ?? 'Sin nombre de obra' }} ({{ formatoMoneda(c.total) }})
                        </SelectItem>
                    </SelectContent>
                </Select>

                <p v-if="pasoCotizacionHabilitado && !cargandoCotizaciones && !cotizaciones.length"
                    class="mt-2 text-xs text-muted-foreground">
                    Este proyecto no tiene cotizaciones aprobadas todavía.
                </p>

                <div v-if="cotizacionSeleccionada"
                    class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400">
                    <CheckCircle2 class="size-3.5 shrink-0" />
                    <span>
                        {{ cotizacionSeleccionada.folio }} · {{ cotizacionSeleccionada.fecha ?? '—' }} ·
                        {{ formatoMoneda(cotizacionSeleccionada.total) }}
                    </span>
                </div>
            </div>

            <!-- Paso 4: Partidas disponibles -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm transition-opacity"
                :class="!pasoPartidasHabilitado ? 'opacity-50' : ''">
                <div class="mb-3 flex items-center gap-2">
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">4</span>
                    <Boxes class="size-4 text-muted-foreground" />
                    <p class="text-sm font-semibold">Actividades disponibles</p>
                    <span v-if="partidas.length" class="ml-auto text-xs text-muted-foreground">{{ partidas.length }}
                        partidas</span>
                </div>

                <div v-if="!pasoPartidasHabilitado" class="py-6 text-center text-sm text-muted-foreground">
                    Selecciona una cotización aprobada para cargar sus partidas.
                </div>

                <div v-else-if="cargandoPartidas" class="py-6 text-center text-sm text-muted-foreground">
                    Cargando partidas…
                </div>

                <div v-else-if="!partidas.length" class="py-6 text-center text-sm text-muted-foreground">
                    Esta cotización no tiene partidas capturadas.
                </div>

                <div v-else class="divide-y rounded-xl border">
                    <div v-for="partida in partidas" :key="partida.id"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm">
                        <Box class="size-4 shrink-0 text-muted-foreground" />
                        <span class="min-w-0 flex-1 truncate">{{ partida.descripcion }}</span>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            {{ partida.cantidad }} {{ partida.unidad ?? '' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
