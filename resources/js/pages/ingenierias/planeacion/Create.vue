<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Boxes,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    FileText,
    Layers,
    Send,
    Trash2,
    User as UserIcon,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

interface EmpleadoOpcion {
    id: number;
    nombre: string;
    puesto: string | null;
}

interface AsignacionCronograma {
    id: string;
    partidaId: number;
    empleadoId: number;
    empleadoNombre: string;
    dia: DiaSemana;
    horas: number;
}

type DiaSemana = 'lunes' | 'martes' | 'miercoles' | 'jueves' | 'viernes' | 'sabado' | 'domingo';

const DIAS_ENUM: DiaSemana[] = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
const DIAS_LABEL: Record<DiaSemana, string> = {
    lunes: 'Lun',
    martes: 'Mar',
    miercoles: 'Mié',
    jueves: 'Jue',
    viernes: 'Vie',
    sabado: 'Sáb',
    domingo: 'Dom',
};

const props = defineProps<{
    plantas: PlantaOpcion[];
    empleados: EmpleadoOpcion[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Planeación', href: PlaneacionController.index() },
            { title: 'Nueva Planeación', href: '' },
        ],
    },
});

// ---------- Paso 1: Semana ----------

const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

const semanaSeleccionada = ref<{ anio: number; semana: number }>(isoWeekInfo(new Date()));
const mesCalendario = ref(lunesDeSemanaIso(semanaSeleccionada.value.anio, semanaSeleccionada.value.semana));

const diasDeLaSemanaSeleccionada = computed(() =>
    diasDeSemana(semanaSeleccionada.value.anio, semanaSeleccionada.value.semana),
);

function formatoFecha(fecha: Date): string {
    return `${fecha.getDate()}/${fecha.getMonth() + 1}`;
}

function seleccionarSemanaDeFecha(fecha: Date): void {
    semanaSeleccionada.value = isoWeekInfo(fecha);
}

function cambiarMesCalendario(delta: number): void {
    mesCalendario.value = new Date(mesCalendario.value.getFullYear(), mesCalendario.value.getMonth() + delta, 1);
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

function onCambiarPlanta(v: string | null): void {
    plantaId.value = v ? Number(v) : null;
    proyectoId.value = null;
    cotizacionId.value = null;
    cotizaciones.value = [];
    partidas.value = [];
    asignaciones.value = [];
}

// ---------- Paso 3: Cotización aprobada ----------

const cotizaciones = ref<CotizacionAprobada[]>([]);
const cotizacionId = ref<number | null>(null);
const cargandoCotizaciones = ref(false);

async function onCambiarProyecto(v: string | null): Promise<void> {
    proyectoId.value = v ? Number(v) : null;
    cotizacionId.value = null;
    cotizaciones.value = [];
    partidas.value = [];
    asignaciones.value = [];

    if (plantaId.value === null || proyectoId.value === null) return;

    cargandoCotizaciones.value = true;
    try {
        const respuesta = await fetch(
            PlaneacionController.cotizacionesAprobadas.url({ planta: plantaId.value, proyecto: proyectoId.value }),
        );
        cotizaciones.value = (await respuesta.json()) as CotizacionAprobada[];
    } finally {
        cargandoCotizaciones.value = false;
    }
}

// ---------- Paso 4: Partidas de la cotización elegida ----------

const partidas = ref<PartidaDisponible[]>([]);
const cargandoPartidas = ref(false);

async function onCambiarCotizacion(v: string | null): Promise<void> {
    cotizacionId.value = v ? Number(v) : null;
    partidas.value = [];
    asignaciones.value = [];

    if (plantaId.value === null || proyectoId.value === null || cotizacionId.value === null) return;

    cargandoPartidas.value = true;
    try {
        const respuesta = await fetch(
            PlaneacionController.partidasDeCotizacion.url({
                planta: plantaId.value,
                proyecto: proyectoId.value,
                cotizacion: cotizacionId.value,
            }),
        );
        partidas.value = (await respuesta.json()) as PartidaDisponible[];
    } finally {
        cargandoPartidas.value = false;
    }
}

// ---------- Preselección desde query string ----------

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
        onCambiarPlanta(String(plantaQuery));

        const proyectoQuery = Number(query.get('proyecto'));
        if (proyectoQuery && proyectosDePlanta.value.some((p) => p.id === proyectoQuery)) {
            onCambiarProyecto(String(proyectoQuery));
        }
    }
});

// ---------- Helpers ----------

function formatoMoneda(valor: number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}

const cotizacionSeleccionada = computed(
    () => cotizaciones.value.find((c) => c.id === cotizacionId.value) ?? null,
);

const pasoProyectoHabilitado = computed(() => plantaId.value !== null);
const pasoCotizacionHabilitado = computed(() => proyectoId.value !== null);
const pasoCronogramaHabilitado = computed(() => cotizacionId.value !== null && partidas.value.length > 0);

// ---------- Cronograma: drag & drop empleado -> celda (partida x día) ----------

const asignaciones = ref<AsignacionCronograma[]>([]);
let contadorId = 0;

function empleadoPorId(id: number): EmpleadoOpcion | undefined {
    return props.empleados.find((e) => e.id === id);
}

function onDragStartEmpleado(evento: DragEvent, empleado: EmpleadoOpcion): void {
    evento.dataTransfer?.setData('text/empleado-id', String(empleado.id));
    evento.dataTransfer!.effectAllowed = 'copy';
}

function onDropCelda(evento: DragEvent, partidaId: number, dia: DiaSemana): void {
    evento.preventDefault();
    const empleadoIdStr = evento.dataTransfer?.getData('text/empleado-id');
    if (!empleadoIdStr) return;

    const empleadoId = Number(empleadoIdStr);
    const empleado = empleadoPorId(empleadoId);
    if (!empleado) return;

    const yaExiste = asignaciones.value.some(
        (a) => a.partidaId === partidaId && a.empleadoId === empleadoId && a.dia === dia,
    );
    if (yaExiste) return;

    asignaciones.value.push({
        id: `a${contadorId++}`,
        partidaId,
        empleadoId,
        empleadoNombre: empleado.nombre,
        dia,
        horas: 8,
    });
}

function celdaAsignaciones(partidaId: number, dia: DiaSemana): AsignacionCronograma[] {
    return asignaciones.value.filter((a) => a.partidaId === partidaId && a.dia === dia);
}

function quitarAsignacion(id: string): void {
    asignaciones.value = asignaciones.value.filter((a) => a.id !== id);
}

const totalHorasCronograma = computed(() =>
    asignaciones.value.reduce((suma, a) => suma + (Number(a.horas) || 0), 0),
);

const empleadosInvolucrados = computed(() => new Set(asignaciones.value.map((a) => a.empleadoId)).size);

// ---------- Guardar / enviar a aprobación ----------

const guardando = ref(false);
const errorGuardado = ref<string | null>(null);

function construirPayload(enviarAprobacion: boolean) {
    return {
        semana: semanaSeleccionada.value.semana,
        anio: semanaSeleccionada.value.anio,
        enviar_aprobacion: enviarAprobacion,
        asignaciones: asignaciones.value.map((a) => ({
            partida_id: a.partidaId,
            empleado_id: a.empleadoId,
            dia_semana: a.dia,
            horas_trabajadas: a.horas,
        })),
    };
}

function guardar(enviarAprobacion: boolean): void {
    errorGuardado.value = null;

    if (plantaId.value === null || proyectoId.value === null) {
        errorGuardado.value = 'Selecciona planta y proyecto.';
        return;
    }

    if (asignaciones.value.length === 0) {
        errorGuardado.value = 'Arrastra al menos un empleado a una actividad para construir el cronograma.';
        return;
    }

    guardando.value = true;

    router.post(
        PlaneacionController.store.url({ planta: plantaId.value, proyecto: proyectoId.value }),
        construirPayload(enviarAprobacion),
        {
            preserveScroll: true,
            onError: (errores) => {
                errorGuardado.value = Object.values(errores)[0] as string ?? 'No se pudo guardar la planeación.';
            },
            onFinish: () => {
                guardando.value = false;
            },
        },
    );
}
</script>

<template>

    <Head title="Nueva Planeación Semanal" />

    <PageLayout title="Nueva Planeación Semanal"
        description="Selecciona semana, proyecto y cotización aprobada, arma el cronograma y envíalo a aprobación">
        <template #breadcrumbs>
            <Link :href="PlaneacionController.index()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <div class="space-y-4">
            <!-- Pasos 1-3: horizontales -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Paso 1: Semana -->
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">1</span>
                            <CalendarDays class="size-3.5 text-muted-foreground" />
                            <p class="text-xs font-semibold">Semana</p>
                        </div>
                        <button type="button" class="text-[11px] text-primary hover:underline" @click="irASemanaActual">
                            Hoy
                        </button>
                    </div>

                    <div class="mb-1.5 flex items-center justify-between">
                        <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarMesCalendario(-1)">
                            <ChevronLeft class="size-3.5" />
                        </button>
                        <p class="text-[11px] font-medium capitalize">
                            {{ nombresMeses[mesCalendario.getMonth()] }} {{ mesCalendario.getFullYear() }}
                        </p>
                        <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarMesCalendario(1)">
                            <ChevronRight class="size-3.5" />
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-0.5 text-center text-[9px] text-muted-foreground">
                        <span v-for="d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']" :key="d">{{ d }}</span>
                    </div>
                    <div class="mt-0.5 grid grid-cols-7 gap-0.5">
                        <button v-for="(celda, idx) in celdasCalendario" :key="idx" type="button"
                            :disabled="!celda.fecha"
                            class="relative flex aspect-square items-center justify-center rounded text-[11px] transition-colors disabled:cursor-default"
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

                    <p class="mt-2 rounded-lg bg-muted/40 px-2 py-1.5 text-center text-[11px] font-medium">
                        Semana {{ semanaSeleccionada.semana }}/{{ semanaSeleccionada.anio }} ·
                        {{ formatoFecha(diasDeLaSemanaSeleccionada[0]) }}–{{ formatoFecha(diasDeLaSemanaSeleccionada[6])
                        }}
                    </p>
                </div>

                <!-- Paso 2: Planta / Proyecto -->
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <span
                            class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">2</span>
                        <Layers class="size-3.5 text-muted-foreground" />
                        <p class="text-xs font-semibold">Planta y proyecto</p>
                    </div>

                    <div class="space-y-3">
                        <div class="grid gap-1.5">
                            <label class="text-[11px] font-medium text-muted-foreground">Planta</label>
                            <Select :model-value="plantaId ? String(plantaId) : undefined"
                                @update:model-value="(v) => onCambiarPlanta(v as string | null)">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona una planta" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="p in plantas" :key="p.id" :value="String(p.id)">
                                        {{ p.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid gap-1.5">
                            <label class="text-[11px] font-medium text-muted-foreground">Proyecto</label>
                            <Select :model-value="proyectoId ? String(proyectoId) : undefined"
                                :disabled="!pasoProyectoHabilitado"
                                @update:model-value="(v) => onCambiarProyecto(v as string | null)">
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="pasoProyectoHabilitado ? 'Selecciona un proyecto' : 'Primero una planta'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="p in proyectosDePlanta" :key="p.id" :value="String(p.id)">
                                        {{ p.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Cotización aprobada -->
                <div class="rounded-2xl border bg-card p-4 shadow-sm transition-opacity"
                    :class="!pasoCotizacionHabilitado ? 'opacity-50' : ''">
                    <div class="mb-3 flex items-center gap-2">
                        <span
                            class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">3</span>
                        <FileText class="size-3.5 text-muted-foreground" />
                        <p class="text-xs font-semibold">Cotización aprobada</p>
                    </div>

                    <Select :model-value="cotizacionId ? String(cotizacionId) : undefined"
                        :disabled="!pasoCotizacionHabilitado || cargandoCotizaciones"
                        @update:model-value="(v) => onCambiarCotizacion(v as string | null)">
                        <SelectTrigger class="w-full">
                            <SelectValue :placeholder="!pasoCotizacionHabilitado
                                ? 'Primero un proyecto'
                                : cargandoCotizaciones
                                    ? 'Cargando…'
                                    : 'Selecciona una cotización'
                                " />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="c in cotizaciones" :key="c.id" :value="String(c.id)">
                                {{ c.folio }} — {{ c.obra ?? 'Sin nombre de obra' }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <p v-if="pasoCotizacionHabilitado && !cargandoCotizaciones && !cotizaciones.length"
                        class="mt-2 text-[11px] text-muted-foreground">
                        Este proyecto no tiene cotizaciones aprobadas todavía.
                    </p>

                    <div v-if="cotizacionSeleccionada"
                        class="mt-2 flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2 py-1.5 text-[11px] text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400">
                        <CheckCircle2 class="size-3.5 shrink-0" />
                        <span>{{ cotizacionSeleccionada.fecha ?? '—' }} · {{ formatoMoneda(cotizacionSeleccionada.total)
                            }}</span>
                    </div>
                </div>
            </div>

            <!-- Paso 4: Cronograma -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm transition-opacity"
                :class="!pasoCronogramaHabilitado ? 'opacity-50' : ''">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">4</span>
                        <Boxes class="size-4 text-muted-foreground" />
                        <p class="text-sm font-semibold">Cronograma de la semana</p>
                    </div>
                    <div v-if="asignaciones.length" class="flex items-center gap-3 text-[11px] text-muted-foreground">
                        <span>{{ totalHorasCronograma.toFixed(1) }}h programadas</span>
                        <span>·</span>
                        <span>{{ empleadosInvolucrados }} empleados</span>
                    </div>
                </div>

                <div v-if="!pasoCronogramaHabilitado" class="py-10 text-center text-sm text-muted-foreground">
                    Selecciona una cotización aprobada con partidas para construir el cronograma.
                </div>

                <div v-else-if="cargandoPartidas" class="py-10 text-center text-sm text-muted-foreground">
                    Cargando partidas…
                </div>

                <template v-else>
                    <!-- Cards de empleados disponibles: origen del drag -->
                    <div class="mb-4 rounded-xl border bg-muted/30 p-3">
                        <p class="mb-2 flex items-center gap-1.5 text-[11px] font-medium text-muted-foreground">
                            <UserIcon class="size-3.5" />
                            Arrastra un empleado sobre una celda del cronograma para asignarlo
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="empleado in props.empleados" :key="empleado.id" draggable="true"
                                @dragstart="onDragStartEmpleado($event, empleado)"
                                class="flex cursor-grab items-center gap-2 rounded-full border bg-background px-3 py-1.5 text-xs shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing">
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                                    {{ empleado.nombre.charAt(0) }}
                                </span>
                                <span class="font-medium">{{ empleado.nombre }}</span>
                                <span v-if="empleado.puesto" class="text-muted-foreground">· {{ empleado.puesto
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla cronograma: filas = partidas, columnas = días de la semana seleccionada -->
                    <div class="overflow-x-auto rounded-xl border">
                        <table class="w-full min-w-[720px] border-collapse text-xs">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th class="w-56 border-b px-3 py-2 text-left font-semibold">Actividad</th>
                                    <th v-for="(dia, i) in diasDeLaSemanaSeleccionada" :key="i"
                                        class="border-b border-l px-2 py-2 text-center font-semibold">
                                        <div>{{ DIAS_LABEL[DIAS_ENUM[i]] }}</div>
                                        <div class="text-[10px] font-normal text-muted-foreground">{{ formatoFecha(dia)
                                            }}</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="partida in partidas" :key="partida.id" class="align-top">
                                    <td class="border-b px-3 py-2">
                                        <p class="font-medium leading-snug">{{ partida.descripcion }}</p>
                                        <p v-if="partida.unidad" class="text-[10px] text-muted-foreground">
                                            {{ partida.cantidad }} {{ partida.unidad }}
                                        </p>
                                    </td>
                                    <td v-for="(dia, i) in diasDeLaSemanaSeleccionada" :key="i"
                                        class="min-w-[110px] border-b border-l p-1 align-top" @dragover.prevent
                                        @drop="onDropCelda($event, partida.id, DIAS_ENUM[i])">
                                        <div
                                            class="flex min-h-[52px] flex-col gap-1 rounded-lg border border-dashed border-transparent p-1 transition-colors hover:border-primary/40 hover:bg-primary/5">
                                            <div v-for="a in celdaAsignaciones(partida.id, DIAS_ENUM[i])" :key="a.id"
                                                class="flex items-center gap-1 rounded-md bg-primary/10 px-1.5 py-1">
                                                <span class="min-w-0 flex-1 truncate font-medium text-primary">
                                                    {{ a.empleadoNombre.split(' ')[0] }}
                                                </span>
                                                <Input type="number" v-model.number="a.horas" min="0.5" max="24"
                                                    step="0.5"
                                                    class="h-6 w-12 border-none bg-background px-1 text-center text-[11px]" />
                                                <button type="button"
                                                    class="shrink-0 text-muted-foreground hover:text-destructive"
                                                    @click="quitarAsignacion(a.id)">
                                                    <Trash2 class="size-3" />
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <!-- Acciones finales -->
            <div
                class="flex flex-col items-end gap-2 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-end">
                <p v-if="errorGuardado" class="mr-auto text-xs text-destructive">{{ errorGuardado }}</p>
                <Button variant="outline" :disabled="guardando || !pasoCronogramaHabilitado" @click="guardar(false)">
                    Guardar borrador
                </Button>
                <Button :disabled="guardando || !pasoCronogramaHabilitado" @click="guardar(true)">
                    <Send class="mr-2 size-4" />
                    Guardar y enviar a aprobación
                </Button>
            </div>
        </div>
    </PageLayout>
</template>
