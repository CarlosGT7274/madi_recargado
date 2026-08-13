<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Box, Boxes, CalendarDays, CheckCircle2, FileText, Layers } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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

// ---------- Paso 1: Semana ----------
// Mismo cálculo ISO-8601 que ya usaba Create.vue: rango razonable de
// semanas (8 atrás, 16 adelante) alrededor de la semana actual.

interface SemanaOpcion {
    key: string;
    semana: number;
    anio: number;
    lunes: Date;
    domingo: Date;
    label: string;
}

function lunesDeSemanaIso(anio: number, semana: number): Date {
    const cuatroEnero = new Date(anio, 0, 4);
    const diaSemanaCuatroEnero = (cuatroEnero.getDay() + 6) % 7;
    const lunesSemanaUno = new Date(cuatroEnero);
    lunesSemanaUno.setDate(cuatroEnero.getDate() - diaSemanaCuatroEnero);

    const resultado = new Date(lunesSemanaUno);
    resultado.setDate(lunesSemanaUno.getDate() + (semana - 1) * 7);
    return resultado;
}

function isoWeekInfo(fecha: Date): { semana: number; anio: number } {
    const copia = new Date(Date.UTC(fecha.getFullYear(), fecha.getMonth(), fecha.getDate()));
    const diaSemana = (copia.getUTCDay() + 6) % 7;
    copia.setUTCDate(copia.getUTCDate() - diaSemana + 3);
    const primerJueves = new Date(Date.UTC(copia.getUTCFullYear(), 0, 4));
    const semana = 1 + Math.round(((copia.getTime() - primerJueves.getTime()) / 86400000 - 3 + ((primerJueves.getUTCDay() + 6) % 7)) / 7);
    return { semana, anio: copia.getUTCFullYear() };
}

function formatoFecha(fecha: Date): string {
    return `${fecha.getDate()}/${fecha.getMonth() + 1}/${fecha.getFullYear()}`;
}

const semanasOpciones = computed<SemanaOpcion[]>(() => {
    const hoy = isoWeekInfo(new Date());
    const opciones: SemanaOpcion[] = [];

    for (let offset = -8; offset <= 16; offset++) {
        const lunes = lunesDeSemanaIso(hoy.anio, hoy.semana + offset);
        const domingo = new Date(lunes);
        domingo.setDate(lunes.getDate() + 6);
        const { semana, anio } = isoWeekInfo(lunes);

        opciones.push({
            key: `${anio}-${semana}`,
            semana,
            anio,
            lunes,
            domingo,
            label: `Semana ${semana} — ${anio}`,
        });
    }

    return opciones;
});

const semanaSeleccionadaKey = ref<string>('');

const semanaActual = computed<SemanaOpcion | null>(
    () => semanasOpciones.value.find((s) => s.key === semanaSeleccionadaKey.value) ?? null,
);

watch(
    semanasOpciones,
    (opciones) => {
        if (!semanaSeleccionadaKey.value && opciones.length) {
            semanaSeleccionadaKey.value = opciones[8]?.key ?? opciones[0].key;
        }
    },
    { immediate: true },
);

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
                <div class="mb-3 flex items-center gap-2">
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                    <CalendarDays class="size-4 text-muted-foreground" />
                    <p class="text-sm font-semibold">Semana de trabajo</p>
                </div>

                <Select v-model="semanaSeleccionadaKey">
                    <SelectTrigger class="w-full sm:w-80">
                        <SelectValue placeholder="Selecciona una semana" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in semanasOpciones" :key="s.key" :value="s.key">
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <p v-if="semanaActual" class="mt-2 text-xs text-muted-foreground">
                    {{ formatoFecha(semanaActual.lunes) }} — {{ formatoFecha(semanaActual.domingo) }}
                </p>
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
