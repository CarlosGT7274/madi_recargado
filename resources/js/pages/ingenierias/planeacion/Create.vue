<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Save, Send, Users } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import ActividadController from '@/actions/App/Http/Controllers/Ingenierias/ActividadController';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
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

interface EmpleadoOpcion {
    id: number;
    nombre: string;
    puesto: string | null;
}

interface ActividadFila {
    id: number;
    descripcion: string;
}

type DiaSemana = 'lunes' | 'martes' | 'miercoles' | 'jueves' | 'viernes' | 'sabado' | 'domingo';

interface AsignacionLocal {
    partidaId: number;
    empleadoId: number;
    empleadoNombre: string;
    dia: DiaSemana;
}

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

const diasSemana: { value: DiaSemana; label: string }[] = [
    { value: 'lunes', label: 'Lunes' },
    { value: 'martes', label: 'Martes' },
    { value: 'miercoles', label: 'Miércoles' },
    { value: 'jueves', label: 'Jueves' },
    { value: 'viernes', label: 'Viernes' },
    { value: 'sabado', label: 'Sábado' },
    { value: 'domingo', label: 'Domingo' },
];

// --- Semana: generamos un rango razonable de opciones (12 atrás, 20 adelante) ---
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
            label: `Semana ${semana}`,
        });
    }

    return opciones;
});

const semanaSeleccionadaKey = ref<string>('');

const semanaActual = computed<SemanaOpcion | null>(
    () => semanasOpciones.value.find((s) => s.key === semanaSeleccionadaKey.value) ?? semanasOpciones.value[8] ?? null,
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

const diasDelRango = computed(() => {
    if (!semanaActual.value) return [];

    return diasSemana.map((dia, indice) => {
        const fecha = new Date(semanaActual.value!.lunes);
        fecha.setDate(fecha.getDate() + indice);
        return { ...dia, fecha };
    });
});

// --- Selección planta / proyecto ---
const plantaId = ref<number | null>(null);
const proyectoId = ref<number | null>(null);

const proyectosDePlanta = computed<ProyectoOpcion[]>(
    () => props.plantas.find((p) => p.id === plantaId.value)?.proyectos ?? [],
);

watch(plantaId, () => {
    proyectoId.value = null;
    actividades.value = [];
    asignaciones.value = [];
});

// --- Actividades del proyecto seleccionado ---
const actividades = ref<ActividadFila[]>([]);
const cargandoActividades = ref(false);

watch(proyectoId, async (nuevoId) => {
    asignaciones.value = [];
    actividades.value = [];

    if (plantaId.value === null || nuevoId === null) return;

    cargandoActividades.value = true;

    try {
        const respuesta = await fetch(ActividadController.data.url({ planta: plantaId.value, proyecto: nuevoId }));
        actividades.value = (await respuesta.json()) as ActividadFila[];
    } finally {
        cargandoActividades.value = false;
    }
});

// --- Drag & drop ---
const empleadoArrastrado = ref<EmpleadoOpcion | null>(null);
const asignaciones = ref<AsignacionLocal[]>([]);

function iniciarArrastre(empleado: EmpleadoOpcion): void {
    empleadoArrastrado.value = empleado;
}

function soltarEnCelda(partidaId: number, dia: DiaSemana): void {
    const empleado = empleadoArrastrado.value;
    if (!empleado) return;

    const yaAsignado = asignaciones.value.some(
        (a) => a.partidaId === partidaId && a.dia === dia && a.empleadoId === empleado.id,
    );

    if (!yaAsignado) {
        asignaciones.value.push({
            partidaId,
            empleadoId: empleado.id,
            empleadoNombre: empleado.nombre,
            dia,
        });
    }

    empleadoArrastrado.value = null;
}

function quitarAsignacion(partidaId: number, dia: DiaSemana, empleadoId: number): void {
    asignaciones.value = asignaciones.value.filter(
        (a) => !(a.partidaId === partidaId && a.dia === dia && a.empleadoId === empleadoId),
    );
}

function asignacionesDeCelda(partidaId: number, dia: DiaSemana): AsignacionLocal[] {
    return asignaciones.value.filter((a) => a.partidaId === partidaId && a.dia === dia);
}

// --- Tabs ---
const tabActivo = ref<'calendario' | 'horas'>('calendario');

// --- Guardar ---
const guardando = ref(false);

function construirPayload() {
    return {
        semana: semanaActual.value?.semana ?? 0,
        anio: semanaActual.value?.anio ?? 0,
        asignaciones: asignaciones.value.map((a) => ({
            partida_id: a.partidaId,
            empleado_id: a.empleadoId,
            dia_semana: a.dia,
        })),
    };
}

function guardar(enviarInmediato: boolean): void {
    if (plantaId.value === null || proyectoId.value === null) return;

    guardando.value = true;

    router.post(
        PlaneacionController.store({ planta: plantaId.value, proyecto: proyectoId.value }).url,
        construirPayload(),
        {
            onSuccess: (page) => {
                if (!enviarInmediato) return;

                const props = page.props as { planeacion?: { id: number } };
                const id = props.planeacion?.id;
                if (id) {
                    router.post(PlaneacionController.enviar(id).url);
                }
            },
            onFinish: () => {
                guardando.value = false;
            },
        },
    );
}

const totalAsignaciones = computed(() => asignaciones.value.length);
</script>

<template>
    <Head title="Nueva Planeación Semanal" />

    <PageLayout title="Nueva Planeación Semanal" description="Crea una nueva planeación de actividades para la semana seleccionada">
        <template #breadcrumbs>
            <Link :href="PlaneacionController.index()" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <template #actions>
            <Button variant="outline" :disabled="guardando || !proyectoId" @click="guardar(false)">
                <Save class="mr-2 size-4" />
                Guardar Borrador
            </Button>
            <Button :disabled="guardando || !proyectoId" @click="guardar(true)">
                <Send class="mr-2 size-4" />
                Enviar a Aprobación
            </Button>
        </template>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="grid gap-1.5">
                <label class="text-sm font-medium">Semana</label>
                <Select v-model="semanaSeleccionadaKey">
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona una semana" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in semanasOpciones" :key="s.key" :value="s.key">
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="semanaActual" class="text-xs text-muted-foreground">
                    {{ formatoFecha(semanaActual.lunes) }} - {{ formatoFecha(semanaActual.domingo) }}
                </p>
            </div>

            <div class="grid gap-1.5">
                <label class="text-sm font-medium">Planta</label>
                <Select :model-value="plantaId ? String(plantaId) : undefined" @update:model-value="(v) => (plantaId = v ? Number(v) : null)">
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
                <label class="text-sm font-medium">Proyecto</label>
                <Select
                    :model-value="proyectoId ? String(proyectoId) : undefined"
                    :disabled="!plantaId"
                    @update:model-value="(v) => (proyectoId = v ? Number(v) : null)"
                >
                    <SelectTrigger>
                        <SelectValue :placeholder="plantaId ? 'Selecciona un proyecto' : 'Primero selecciona una planta'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="p in proyectosDePlanta" :key="p.id" :value="String(p.id)">
                            {{ p.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="mb-4 flex gap-1 border-b">
            <button
                type="button"
                class="border-b-2 px-3 py-2 text-sm font-medium"
                :class="tabActivo === 'calendario' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground'"
                @click="tabActivo = 'calendario'"
            >
                Calendario Semanal
            </button>
            <button
                type="button"
                class="border-b-2 px-3 py-2 text-sm font-medium"
                :class="tabActivo === 'horas' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground'"
                @click="tabActivo = 'horas'"
            >
                Registro de Horas
            </button>
        </div>

        <template v-if="tabActivo === 'calendario'">
            <div v-if="semanaActual" class="mb-4 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-700 px-6 py-5 text-white shadow-sm">
                <p class="text-lg font-bold">Semana {{ semanaActual.semana }} - {{ semanaActual.anio }}</p>
                <div class="mt-1 flex items-center justify-between text-sm text-blue-100">
                    <span>{{ formatoFecha(semanaActual.lunes) }} - {{ formatoFecha(semanaActual.domingo) }}</span>
                    <span>{{ actividades.length }} actividades | {{ totalAsignaciones }} asignaciones</span>
                </div>
            </div>

            <div class="mb-4 rounded-2xl border bg-card p-4 shadow-sm">
                <div class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                    <Users class="size-4" />
                    Empleados - Arrastra a las celdas
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <div
                        v-for="empleado in empleados"
                        :key="empleado.id"
                        draggable="true"
                        class="flex cursor-grab items-center gap-2 rounded-xl border bg-card p-3 shadow-sm active:cursor-grabbing"
                        @dragstart="iniciarArrastre(empleado)"
                    >
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                            {{ empleado.nombre.charAt(0) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ empleado.nombre }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ empleado.puesto ?? 'Sin rol' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border shadow-sm">
                <div class="grid grid-cols-8 bg-red-600 text-white">
                    <div class="px-4 py-3 text-sm font-semibold">Actividades</div>
                    <div v-for="dia in diasDelRango" :key="dia.value" class="px-2 py-3 text-center text-xs font-semibold">
                        {{ dia.label }}
                        <p class="font-normal opacity-80">{{ String(dia.fecha.getDate()).padStart(2, '0') }}-{{ dia.fecha.toLocaleDateString('es-MX', { month: 'short' }) }}</p>
                    </div>
                </div>

                <div v-if="cargandoActividades" class="flex items-center justify-center gap-2 py-16 text-sm text-muted-foreground">
                    Cargando actividades…
                </div>

                <div v-else-if="!proyectoId" class="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                    <FileText class="size-6" />
                    Selecciona planta y proyecto para ver las actividades disponibles
                </div>

                <div v-else-if="!actividades.length" class="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                    <FileText class="size-6" />
                    No hay actividades programadas
                </div>

                <div v-for="actividad in actividades" :key="actividad.id" class="grid grid-cols-8 border-t">
                    <div class="flex items-center px-4 py-2 text-sm font-medium">{{ actividad.descripcion }}</div>

                    <div
                        v-for="dia in diasDelRango"
                        :key="dia.value"
                        class="min-h-16 border-l px-1 py-1"
                        @dragover.prevent
                        @drop="soltarEnCelda(actividad.id, dia.value)"
                    >
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="asig in asignacionesDeCelda(actividad.id, dia.value)"
                                :key="`${asig.empleadoId}-${asig.dia}`"
                                class="flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                            >
                                {{ asig.empleadoNombre }}
                                <button
                                    type="button"
                                    class="text-primary/60 hover:text-primary"
                                    @click="quitarAsignacion(actividad.id, dia.value, asig.empleadoId)"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div v-else class="flex flex-col items-center gap-2 rounded-2xl border border-dashed bg-card/50 py-16 text-center text-sm text-muted-foreground">
            Registro de Horas — pendiente de definir con nómina.
        </div>
    </PageLayout>
</template>
