<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Building2,
    CalendarRange,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock,
    FolderOpen,
    Send,
    Users,
    X,
    XCircle,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type EstadoPlaneacion = 'borrador' | 'enviada' | 'aprobada' | 'rechazada';

interface ProyectoRef {
    id: number;
    nombre: string;
    folio: string;
}

interface PlantaRef {
    id: number;
    nombre: string;
}

interface EmpleadoRef {
    id: number;
    nombre: string;
}

interface PartidaRef {
    id: number;
    descripcion: string;
}

interface PlaneacionResumen {
    id: number;
    semana: number;
    anio: number;
    estado: EstadoPlaneacion;
    reportadaNomina: boolean;
    proyecto: ProyectoRef | null;
    planta: PlantaRef | null;
    residente: string | null;
    residenteId: number | null;
    aprobador: string | null;
    fechaInicio: string;
    fechaFin: string;
    horasProgramadas: number;
    incidenciasCount: number;
    empleados: EmpleadoRef[];
    partidas: PartidaRef[];
}

interface FiltroPlanta {
    id: number;
    nombre: string;
}

interface FiltroProyecto {
    id: number;
    nombre: string;
    planta_id: number;
}

interface FiltroResidente {
    id: number;
    nombre: string;
}

interface FiltroOpciones {
    plantas: FiltroPlanta[];
    proyectos: FiltroProyecto[];
    residentes: FiltroResidente[];
}

const props = defineProps<{
    /**
     * Independiente del acceso a esta vista (ya requirió `supervisar` en
     * el backend). Controla ÚNICAMENTE si se muestran los botones de
     * Aprobar/Rechazar en el drill-down — alguien puede supervisar sin
     * poder aprobar.
     */
    puedeAprobar: boolean;
    filtros?: FiltroOpciones;
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Programación', href: PlaneacionController.index() }],
    },
});

// ---------- Año y calendario ----------

const anioActual = ref(new Date().getFullYear());

function cambiarAnio(delta: number): void {
    anioActual.value += delta;
}

function irAHoy(): void {
    anioActual.value = new Date().getFullYear();
    seleccionarDia(toIso(new Date()));
}

const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];
const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

function toIso(fecha: Date): string {
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
}

interface CeldaMes {
    fecha: Date | null;
    iso: string | null;
}

function celdasDeMes(anio: number, mes: number): CeldaMes[] {
    const primerDia = new Date(anio, mes, 1);
    const inicioOffset = (primerDia.getDay() + 6) % 7;
    const totalDias = new Date(anio, mes + 1, 0).getDate();

    const celdas: CeldaMes[] = [];
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
        primerDiaIso: toIso(new Date(anioActual.value, indice, 1)),
        ultimoDiaIso: toIso(new Date(anioActual.value, indice + 1, 0)),
    })),
);

// ---------- Filtros ----------

const filtroPlantaId = ref<number | null>(null);
const filtroProyectoId = ref<number | null>(null);
const filtroResidenteId = ref<number | null>(null);
const filtroEstados = ref<Set<EstadoPlaneacion>>(new Set());

watch(filtroPlantaId, () => {
    filtroProyectoId.value = null;
});

const proyectosDisponibles = computed<FiltroProyecto[]>(() => {
    const todos = props.filtros?.proyectos ?? [];
    if (filtroPlantaId.value === null) return todos;
    return todos.filter((p) => p.planta_id === filtroPlantaId.value);
});

function toggleEstado(estado: EstadoPlaneacion): void {
    const nuevo = new Set(filtroEstados.value);
    if (nuevo.has(estado)) {
        nuevo.delete(estado);
    } else {
        nuevo.add(estado);
    }
    filtroEstados.value = nuevo;
}

function limpiarFiltros(): void {
    filtroPlantaId.value = null;
    filtroProyectoId.value = null;
    filtroResidenteId.value = null;
    filtroEstados.value = new Set();
}

const hayFiltrosActivos = computed(
    () => filtroPlantaId.value !== null || filtroProyectoId.value !== null || filtroResidenteId.value !== null || filtroEstados.value.size > 0,
);

const planeacionesFiltradas = computed<PlaneacionResumen[]>(() => {
    return (props.planeaciones ?? []).filter((p) => {
        if (filtroPlantaId.value !== null && p.planta?.id !== filtroPlantaId.value) return false;
        if (filtroProyectoId.value !== null && p.proyecto?.id !== filtroProyectoId.value) return false;
        if (filtroResidenteId.value !== null && p.residenteId !== filtroResidenteId.value) return false;
        if (filtroEstados.value.size > 0 && !filtroEstados.value.has(p.estado)) return false;
        return true;
    });
});

// ---------- Agregación por día (carga + estados + incidencias) ----------

interface DiaAgregado {
    horas: number;
    estados: Set<EstadoPlaneacion>;
    incidencias: boolean;
}

function fechasDelRango(inicioIso: string, finIso: string): string[] {
    const resultado: string[] = [];
    const cursor = new Date(`${inicioIso}T00:00:00`);
    const finDate = new Date(`${finIso}T00:00:00`);

    while (cursor <= finDate) {
        resultado.push(toIso(cursor));
        cursor.setDate(cursor.getDate() + 1);
    }

    return resultado;
}

const mapaDias = computed<Map<string, DiaAgregado>>(() => {
    const mapa = new Map<string, DiaAgregado>();

    for (const p of planeacionesFiltradas.value) {
        const dias = fechasDelRango(p.fechaInicio, p.fechaFin);
        const horasPorDia = dias.length > 0 ? p.horasProgramadas / dias.length : 0;

        for (const iso of dias) {
            const actual = mapa.get(iso) ?? { horas: 0, estados: new Set<EstadoPlaneacion>(), incidencias: false };
            actual.horas += horasPorDia;
            actual.estados.add(p.estado);
            if (p.incidenciasCount > 0) actual.incidencias = true;
            mapa.set(iso, actual);
        }
    }

    return mapa;
});

const maxHorasDia = computed(() => {
    let max = 0;
    for (const dia of mapaDias.value.values()) {
        if (dia.horas > max) max = dia.horas;
    }
    return max;
});

function intensidadClase(iso: string | null): string {
    if (!iso) return '';
    const dia = mapaDias.value.get(iso);
    if (!dia || dia.horas <= 0 || maxHorasDia.value <= 0) return '';

    const ratio = dia.horas / maxHorasDia.value;
    if (ratio > 0.75) return 'bg-primary/70';
    if (ratio > 0.5) return 'bg-primary/45';
    if (ratio > 0.25) return 'bg-primary/25';
    return 'bg-primary/10';
}

function estadosDelDia(iso: string | null): EstadoPlaneacion[] {
    if (!iso) return [];
    return Array.from(mapaDias.value.get(iso)?.estados ?? []);
}

function tieneIncidencia(iso: string | null): boolean {
    if (!iso) return false;
    return mapaDias.value.get(iso)?.incidencias ?? false;
}

const puntoClase: Record<EstadoPlaneacion, string> = {
    borrador: 'bg-muted-foreground',
    enviada: 'bg-blue-500',
    aprobada: 'bg-emerald-500',
    rechazada: 'bg-red-500',
};

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

// ---------- Selección de rango (arbitrario, cruza meses) ----------

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

function seleccionarMesCompleto(mes: { primerDiaIso: string; ultimoDiaIso: string }): void {
    rangoInicio.value = mes.primerDiaIso;
    rangoFin.value = mes.ultimoDiaIso;
}

function limpiarSeleccion(): void {
    rangoInicio.value = null;
    rangoFin.value = null;
}

const finEfectivo = computed<string | null>(() => rangoFin.value ?? rangoInicio.value);

function diaEnRango(iso: string | null): boolean {
    if (!iso || !rangoInicio.value || !finEfectivo.value) return false;
    return iso >= rangoInicio.value && iso <= finEfectivo.value;
}

function esExtremoRango(iso: string | null): boolean {
    if (!iso || !rangoInicio.value) return false;
    return iso === rangoInicio.value || iso === finEfectivo.value;
}

// ---------- Planeaciones del rango seleccionado + resumen ----------

const planeacionesEnRango = computed<PlaneacionResumen[]>(() => {
    if (!rangoInicio.value || !finEfectivo.value) return [];

    const inicio = rangoInicio.value;
    const fin = finEfectivo.value;

    return planeacionesFiltradas.value
        .filter((p) => p.fechaInicio <= fin && p.fechaFin >= inicio)
        .sort((a, b) => a.fechaInicio.localeCompare(b.fechaInicio));
});

interface ResumenRango {
    total: number;
    porEstado: Record<EstadoPlaneacion, number>;
    horasTotal: number;
    incidenciasTotal: number;
    empleados: EmpleadoRef[];
    proyectos: { id: number; nombre: string }[];
    plantas: { id: number; nombre: string }[];
}

const resumenRango = computed<ResumenRango>(() => {
    const lista = planeacionesEnRango.value;
    const porEstado: Record<EstadoPlaneacion, number> = { borrador: 0, enviada: 0, aprobada: 0, rechazada: 0 };
    let horasTotal = 0;
    let incidenciasTotal = 0;
    const empleadosMapa = new Map<number, string>();
    const proyectosMapa = new Map<number, string>();
    const plantasMapa = new Map<number, string>();

    for (const p of lista) {
        porEstado[p.estado]++;
        horasTotal += p.horasProgramadas;
        incidenciasTotal += p.incidenciasCount;
        for (const e of p.empleados) empleadosMapa.set(e.id, e.nombre);
        if (p.proyecto) proyectosMapa.set(p.proyecto.id, p.proyecto.nombre);
        if (p.planta) plantasMapa.set(p.planta.id, p.planta.nombre);
    }

    return {
        total: lista.length,
        porEstado,
        horasTotal,
        incidenciasTotal,
        empleados: Array.from(empleadosMapa, ([id, nombre]) => ({ id, nombre })),
        proyectos: Array.from(proyectosMapa, ([id, nombre]) => ({ id, nombre })),
        plantas: Array.from(plantasMapa, ([id, nombre]) => ({ id, nombre })),
    };
});

function rangoLabel(): string {
    if (!rangoInicio.value) return '';
    if (!rangoFin.value || rangoFin.value === rangoInicio.value) return rangoInicio.value;
    return `${rangoInicio.value} — ${rangoFin.value}`;
}

// ---------- Acciones ----------

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

    <Head title="Programación" />

    <PageLayout title="Programación"
        description="Overview anual de planeaciones: selecciona un rango para ver el detalle">
        <Deferred :data="['planeaciones', 'filtros']">
            <template #fallback>
                <div class="h-96 animate-pulse rounded-2xl border bg-card/50" />
            </template>

            <div v-if="planeaciones" class="space-y-4">
                <!-- Controles: año + filtros -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-1">
                        <button type="button" class="rounded-md p-1.5 hover:bg-accent" @click="cambiarAnio(-1)">
                            <ChevronLeft class="size-4" />
                        </button>
                        <p class="w-16 text-center text-lg font-semibold">{{ anioActual }}</p>
                        <button type="button" class="rounded-md p-1.5 hover:bg-accent" @click="cambiarAnio(1)">
                            <ChevronRight class="size-4" />
                        </button>
                        <Button variant="ghost" size="sm" class="ml-2" @click="irAHoy">Hoy</Button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Select :model-value="filtroPlantaId ? String(filtroPlantaId) : 'todas'"
                            @update:model-value="(v) => (filtroPlantaId = v === 'todas' ? null : Number(v))">
                            <SelectTrigger class="h-8 w-36 text-xs">
                                <SelectValue placeholder="Planta" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todas">Todas las plantas</SelectItem>
                                <SelectItem v-for="p in filtros?.plantas ?? []" :key="p.id" :value="String(p.id)">
                                    {{ p.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="filtroProyectoId ? String(filtroProyectoId) : 'todos'"
                            @update:model-value="(v) => (filtroProyectoId = v === 'todos' ? null : Number(v))">
                            <SelectTrigger class="h-8 w-40 text-xs">
                                <SelectValue placeholder="Proyecto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los proyectos</SelectItem>
                                <SelectItem v-for="p in proyectosDisponibles" :key="p.id" :value="String(p.id)">
                                    {{ p.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="filtroResidenteId ? String(filtroResidenteId) : 'todos'"
                            @update:model-value="(v) => (filtroResidenteId = v === 'todos' ? null : Number(v))">
                            <SelectTrigger class="h-8 w-36 text-xs">
                                <SelectValue placeholder="Residente" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                <SelectItem v-for="r in filtros?.residentes ?? []" :key="r.id" :value="String(r.id)">
                                    {{ r.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="flex items-center gap-1 rounded-lg border p-0.5">
                            <button v-for="estado in (Object.keys(estadoLabel) as EstadoPlaneacion[])" :key="estado"
                                type="button"
                                class="flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium transition-colors"
                                :class="filtroEstados.has(estado) ? estadoBadgeClass[estado] : 'text-muted-foreground hover:bg-accent'"
                                @click="toggleEstado(estado)">
                                <span class="size-1.5 rounded-full" :class="puntoClase[estado]" />
                                {{ estadoLabel[estado] }}
                            </button>
                        </div>

                        <Button v-if="hayFiltrosActivos" variant="ghost" size="sm" class="text-xs"
                            @click="limpiarFiltros">
                            <X class="mr-1 size-3.5" />
                            Limpiar filtros
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
                    <!-- Grid de 12 meses -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="mes in meses" :key="mes.indice" class="rounded-xl border bg-card p-3 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold">{{ mes.nombre }}</p>
                                <button type="button"
                                    class="text-[10px] text-muted-foreground hover:text-primary hover:underline"
                                    @click="seleccionarMesCompleto(mes)">
                                    Ver mes
                                </button>
                            </div>

                            <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] text-muted-foreground">
                                <span v-for="(d, i) in diasSemana" :key="`${mes.indice}-${d}-${i}`">{{ d }}</span>
                            </div>

                            <div class="mt-0.5 grid grid-cols-7 gap-0.5">
                                <button v-for="(celda, idx) in mes.celdas" :key="idx" type="button"
                                    :disabled="!celda.fecha"
                                    class="relative flex aspect-square flex-col items-center justify-center gap-0.5 rounded text-[11px] transition-colors disabled:cursor-default"
                                    :class="[
                                        !celda.fecha ? 'invisible' : '',
                                        celda.fecha && !diaEnRango(celda.iso) ? intensidadClase(celda.iso) || 'hover:bg-accent' : '',
                                        diaEnRango(celda.iso) && !esExtremoRango(celda.iso) ? 'bg-primary/20' : '',
                                        esExtremoRango(celda.iso) ? 'bg-primary text-primary-foreground' : '',
                                    ]" @click="celda.iso && seleccionarDia(celda.iso)">
                                    {{ celda.fecha?.getDate() }}

                                    <span v-if="celda.iso" class="flex items-center gap-0.5">
                                        <span v-for="(estado, i2) in estadosDelDia(celda.iso).slice(0, 3)" :key="i2"
                                            class="size-1 rounded-full" :class="puntoClase[estado]" />
                                    </span>

                                    <AlertTriangle v-if="tieneIncidencia(celda.iso)"
                                        class="absolute right-0 top-0 size-2.5 text-amber-500" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral: resumen + drill-down -->
                    <div class="xl:sticky xl:top-4 xl:self-start">
                        <div v-if="!rangoInicio"
                            class="rounded-2xl border border-dashed bg-card/50 p-6 text-center shadow-sm">
                            <CalendarRange class="mx-auto size-8 text-muted-foreground" />
                            <p class="mt-3 text-sm font-medium">Selecciona un rango</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Haz clic en un día para empezar, y clic en otro día (aunque sea de otro mes) para cerrar
                                el rango.
                                También puedes usar "Ver mes" en cualquier tarjeta.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold">{{ rangoLabel() }}</p>
                                    <button type="button" class="text-xs text-muted-foreground hover:underline"
                                        @click="limpiarSeleccion">
                                        Limpiar
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div class="rounded-lg bg-muted/40 p-2">
                                        <p class="text-lg font-bold">{{ resumenRango.total }}</p>
                                        <p class="text-[10px] uppercase text-muted-foreground">Planeaciones</p>
                                    </div>
                                    <div class="rounded-lg bg-muted/40 p-2">
                                        <p class="text-lg font-bold">{{ resumenRango.horasTotal.toFixed(0) }}h</p>
                                        <p class="text-[10px] uppercase text-muted-foreground">Horas programadas</p>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <Badge v-for="estado in (Object.keys(estadoLabel) as EstadoPlaneacion[])"
                                        v-show="resumenRango.porEstado[estado] > 0" :key="estado"
                                        :class="estadoBadgeClass[estado]" class="text-[10px]">
                                        {{ resumenRango.porEstado[estado] }} {{ estadoLabel[estado] }}
                                    </Badge>
                                    <Badge v-if="resumenRango.incidenciasTotal > 0"
                                        class="gap-1 bg-amber-500/10 text-[10px] text-amber-600">
                                        <AlertTriangle class="size-3" />
                                        {{ resumenRango.incidenciasTotal }} incidencias
                                    </Badge>
                                </div>

                                <div v-if="resumenRango.plantas.length"
                                    class="mt-3 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <Building2 class="mt-0.5 size-3.5 shrink-0" />
                                    <span>{{resumenRango.plantas.map((p) => p.nombre).join(', ')}}</span>
                                </div>
                                <div v-if="resumenRango.proyectos.length"
                                    class="mt-1.5 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <FolderOpen class="mt-0.5 size-3.5 shrink-0" />
                                    <span>{{resumenRango.proyectos.map((p) => p.nombre).join(', ')}}</span>
                                </div>
                                <div v-if="resumenRango.empleados.length"
                                    class="mt-1.5 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <Users class="mt-0.5 size-3.5 shrink-0" />
                                    <span>{{ resumenRango.empleados.length }} empleados involucrados</span>
                                </div>
                            </div>

                            <div class="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                                <div v-if="!planeacionesEnRango.length"
                                    class="rounded-2xl border bg-card p-6 text-center text-sm text-muted-foreground shadow-sm">
                                    Sin planeaciones en este rango.
                                </div>

                                <div v-for="p in planeacionesEnRango" :key="p.id"
                                    class="rounded-xl border bg-card p-3 shadow-sm">
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
                                        <p class="text-xs text-muted-foreground">Residente: {{ p.residente ?? '—' }}</p>
                                    </Link>

                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Clock class="size-3" /> {{ p.horasProgramadas.toFixed(1) }}h
                                        </span>
                                        <span v-if="p.empleados.length" class="flex items-center gap-1">
                                            <Users class="size-3" /> {{ p.empleados.length }}
                                        </span>
                                        <span v-if="p.incidenciasCount > 0"
                                            class="flex items-center gap-1 text-amber-600">
                                            <AlertTriangle class="size-3" /> {{ p.incidenciasCount }}
                                        </span>
                                    </div>

                                    <div v-if="p.partidas.length" class="mt-1.5 flex flex-wrap gap-1">
                                        <span v-for="partida in p.partidas.slice(0, 3)" :key="partida.id"
                                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                            {{ partida.descripcion }}
                                        </span>
                                        <span v-if="p.partidas.length > 3" class="text-[10px] text-muted-foreground">
                                            +{{ p.partidas.length - 3 }} más
                                        </span>
                                    </div>

                                    <div v-if="p.estado === 'enviada' && props.puedeAprobar" class="mt-2 flex gap-2">
                                        <Button size="sm"
                                            class="h-7 flex-1 bg-emerald-600 text-xs text-white hover:bg-emerald-700"
                                            @click.stop.prevent="aprobar(p.id)">
                                            <CheckCircle2 class="mr-1 size-3" />
                                            Aprobar
                                        </Button>
                                        <Button size="sm" variant="outline"
                                            class="h-7 flex-1 border-red-300 text-xs text-red-600 hover:bg-red-50"
                                            @click.stop.prevent="rechazar(p.id)">
                                            <XCircle class="mr-1 size-3" />
                                            Rechazar
                                        </Button>
                                    </div>
                                    <Button v-else-if="p.estado === 'aprobada' && !p.reportadaNomina" size="sm"
                                        variant="outline" class="mt-2 h-7 w-full text-xs"
                                        @click.stop.prevent="reportarNomina(p.id)">
                                        <Send class="mr-1 size-3" />
                                        Reportar a nómina
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Deferred>
    </PageLayout>
</template>
