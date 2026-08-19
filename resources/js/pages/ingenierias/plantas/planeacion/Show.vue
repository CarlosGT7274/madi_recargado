<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Building2,
    Calendar as CalendarIcon,
    CheckCircle2,
    ChevronDown,
    Clock,
    FolderOpen,
    MessageSquareText,
    Pencil,
    Send,
    Trash2,
    User as UserIcon,
    Users,
    X,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type EstadoPlaneacion = 'borrador' | 'enviada' | 'aprobada' | 'rechazada';
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

const TIPO_INCIDENCIA_LABEL: Record<string, string> = {
    falta: 'Falta',
    vacaciones: 'Vacaciones',
    cambio_dia: 'Cambio de día',
    movimiento: 'Movimiento',
    enfermedad: 'Enfermedad',
    horas_extra: 'Horas extra',
    otro: 'Observación',
};

interface PlantaRef {
    id: number;
    nombre: string;
}

interface ProyectoRef {
    id: number;
    nombre: string;
    folio: string;
}

interface CorteInfo {
    fecha: string;
    vencido: boolean;
}

interface PlaneacionDetalle {
    id: number;
    semana: number;
    anio: number;
    estado: EstadoPlaneacion;
    reportadaNomina: boolean;
    fechaReporteNomina: string | null;
    fechaInicio: string;
    fechaFin: string;
    fechaEnvio: string | null;
    fechaAprobacion: string | null;
    fechaRechazo: string | null;
    comentariosAprobacion: string | null;
    planta: PlantaRef;
    proyecto: ProyectoRef;
    residente: { id: number | null; nombre: string | null; firmaUrl: string | null };
    aprobador: string | null;
    corte: CorteInfo | null;
    puedeEnviar: boolean;
    puedeEliminar: boolean;
    puedeEditar: boolean;
}

interface IncidenciaAsignacion {
    id: number;
    tipo: string;
    diaAnterior: string | null;
    diaNuevo: string | null;
    horasExtra: number | null;
    fecha: string | null;
    notas: string | null;
    creada: string | null;
}

interface EmpleadoAsignado {
    id: number;
    empleado: { id: number; nombre: string };
    diaSemana: DiaSemana;
    estado: string;
    horasTrabajadas: number;
    horasExtra: number;
    incidencias: IncidenciaAsignacion[];
}

interface DiaCronograma {
    dia: DiaSemana;
    horasTotal: number;
    empleados: EmpleadoAsignado[];
}

interface PartidaCronograma {
    partida: { id: number; descripcion: string; unidad: string | null; cantidad: number };
    horasTotal: number;
    dias: DiaCronograma[];
}

interface EmpleadoOpcion {
    id: number;
    nombre: string;
    puesto: string | null;
}

interface PartidaArbolNodo {
    id: number;
    codigo: string | number;
    nombre: string;
    hijas: PartidaArbolNodo[];
}

const props = withDefaults(
    defineProps<{
        planeacion: PlaneacionDetalle;
        cronograma: PartidaCronograma[];
        puedeAprobar: boolean;
        empleados: EmpleadoOpcion[];
        partidasDisponibles: PartidaArbolNodo[];
    }>(),
    {
        cronograma: () => [],
        empleados: () => [],
        partidasDisponibles: () => [],
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Planeación', href: PlaneacionController.index() },
            { title: 'Detalle', href: '' },
        ],
    },
});

const estadoConfig: Record<EstadoPlaneacion, { label: string; badge: string; icon: typeof CheckCircle2 }> = {
    borrador: { label: 'Borrador', badge: 'bg-muted text-muted-foreground', icon: Clock },
    enviada: { label: 'Pendiente de aprobación', badge: 'bg-amber-500/10 text-amber-600', icon: Clock },
    aprobada: { label: 'Aprobada', badge: 'bg-emerald-500/10 text-emerald-600', icon: CheckCircle2 },
    rechazada: { label: 'Rechazada', badge: 'bg-red-500/10 text-red-600', icon: XCircle },
};

// ---------- Totales de solo lectura ----------

const totalHoras = computed(() => props.cronograma.reduce((suma, p) => suma + p.horasTotal, 0));
const totalEmpleados = computed(
    () =>
        new Set(props.cronograma.flatMap((p) => p.dias.flatMap((d) => d.empleados.map((e) => e.empleado.id)))).size,
);

// ---------- Observaciones por empleado: aplanadas y con contexto claro ----------

interface ObservacionPlana {
    id: number;
    empleadoNombre: string;
    partidaDescripcion: string;
    dia: DiaSemana;
    tipo: string;
    notas: string | null;
    creada: string | null;
}

const observaciones = computed<ObservacionPlana[]>(() =>
    props.cronograma.flatMap((grupo) =>
        grupo.dias.flatMap((dia) =>
            dia.empleados.flatMap((asig) =>
                asig.incidencias.map((inc) => ({
                    id: inc.id,
                    empleadoNombre: asig.empleado.nombre,
                    partidaDescripcion: grupo.partida.descripcion,
                    dia: dia.dia,
                    tipo: inc.tipo,
                    notas: inc.notas,
                    creada: inc.creada,
                })),
            ),
        ),
    ),
);

// ---------- Modo edición: editar / corregir cronograma rechazado ----------

interface PartidaPlana {
    id: number;
    descripcion: string;
}

function aplanarPartidas(nodos: PartidaArbolNodo[], prefijo = ''): PartidaPlana[] {
    return nodos.flatMap((nodo) => {
        const etiqueta = prefijo === '' ? nodo.nombre : `${prefijo} · ${nodo.nombre}`;
        if (!nodo.hijas.length) {
            return [{ id: nodo.id, descripcion: etiqueta }];
        }
        return aplanarPartidas(nodo.hijas, etiqueta);
    });
}

const partidasPlanas = computed<PartidaPlana[]>(() => aplanarPartidas(props.partidasDisponibles));

interface AsignacionEditable {
    id: string;
    partidaId: number;
    partidaDescripcion: string;
    empleadoId: number;
    empleadoNombre: string;
    dia: DiaSemana;
    horas: number;
}

const editando = ref(false);
let contadorLocal = 0;

function cronogramaAAsignacionesEditables(): AsignacionEditable[] {
    return props.cronograma.flatMap((grupo) =>
        grupo.dias.flatMap((dia) =>
            dia.empleados.map((a) => ({
                id: `existente-${a.id}`,
                partidaId: grupo.partida.id,
                partidaDescripcion: grupo.partida.descripcion,
                empleadoId: a.empleado.id,
                empleadoNombre: a.empleado.nombre,
                dia: dia.dia,
                horas: a.horasTrabajadas,
            })),
        ),
    );
}

const asignacionesEditables = ref<AsignacionEditable[]>([]);

/**
 * Snapshot FIJO de filas visibles en modo edición, tomado una sola vez al
 * entrar. Si esto se recalculara en cada render mezclando partidasPlanas +
 * asignacionesEditables, borrar la última asignación de una fila que solo
 * existía por tener esa asignación la haría desaparecer por completo.
 */
const partidasParaEdicion = ref<PartidaPlana[]>([]);
const notaCorreccion = ref('');
const notaVisible = ref(false);

function iniciarEdicion(): void {
    asignacionesEditables.value = cronogramaAAsignacionesEditables();

    const usadas = new Map<number, string>();
    partidasPlanas.value.forEach((p) => usadas.set(p.id, p.descripcion));
    asignacionesEditables.value.forEach((a) => usadas.set(a.partidaId, a.partidaDescripcion));
    partidasParaEdicion.value = Array.from(usadas, ([id, descripcion]) => ({ id, descripcion }));

    notaCorreccion.value = '';
    notaVisible.value = false;
    editando.value = true;
}

function cancelarEdicion(): void {
    editando.value = false;
    asignacionesEditables.value = [];
    partidasParaEdicion.value = [];
}

function onDragStartEmpleado(evento: DragEvent, empleado: EmpleadoOpcion): void {
    evento.dataTransfer?.setData('text/empleado-id', String(empleado.id));
    evento.dataTransfer!.effectAllowed = 'copy';
}

/** Chips ya colocados también son draggable: permite mover entre días/actividades sin borrar. */
function onDragStartAsignacion(evento: DragEvent, asignacion: AsignacionEditable): void {
    evento.dataTransfer?.setData('text/asignacion-id', asignacion.id);
    evento.dataTransfer!.effectAllowed = 'move';
}

function onDropCelda(evento: DragEvent, partidaId: number, partidaDescripcion: string, dia: DiaSemana): void {
    evento.preventDefault();

    const asignacionIdMovida = evento.dataTransfer?.getData('text/asignacion-id');
    if (asignacionIdMovida) {
        const asignacion = asignacionesEditables.value.find((a) => a.id === asignacionIdMovida);
        if (!asignacion) return;

        const yaExisteEnDestino = asignacionesEditables.value.some(
            (a) =>
                a.id !== asignacionIdMovida &&
                a.partidaId === partidaId &&
                a.empleadoId === asignacion.empleadoId &&
                a.dia === dia,
        );
        if (yaExisteEnDestino) return;

        asignacion.partidaId = partidaId;
        asignacion.partidaDescripcion = partidaDescripcion;
        asignacion.dia = dia;
        return;
    }

    const empleadoIdStr = evento.dataTransfer?.getData('text/empleado-id');
    if (!empleadoIdStr) return;

    const empleadoId = Number(empleadoIdStr);
    const empleado = props.empleados.find((e) => e.id === empleadoId);
    if (!empleado) return;

    const yaExiste = asignacionesEditables.value.some(
        (a) => a.partidaId === partidaId && a.empleadoId === empleadoId && a.dia === dia,
    );
    if (yaExiste) return;

    asignacionesEditables.value.push({
        id: `nueva-${contadorLocal++}`,
        partidaId,
        partidaDescripcion,
        empleadoId,
        empleadoNombre: empleado.nombre,
        dia,
        horas: 8,
    });
}

function celdaEditable(partidaId: number, dia: DiaSemana): AsignacionEditable[] {
    return asignacionesEditables.value.filter((a) => a.partidaId === partidaId && a.dia === dia);
}

function quitarAsignacionEditable(id: string): void {
    asignacionesEditables.value = asignacionesEditables.value.filter((a) => a.id !== id);
}

const totalHorasEdicion = computed(() => asignacionesEditables.value.reduce((s, a) => s + (Number(a.horas) || 0), 0));

const guardandoCorreccion = ref(false);
const errorCorreccion = ref<string | null>(null);

function guardarCorreccion(enviarAprobacion: boolean): void {
    errorCorreccion.value = null;

    if (!asignacionesEditables.value.length) {
        errorCorreccion.value = 'Agrega al menos una asignación antes de guardar.';
        return;
    }

    guardandoCorreccion.value = true;

    router.patch(
        `/planeacion/${props.planeacion.id}/cronograma`,
        {
            enviar_aprobacion: enviarAprobacion,
            nota: notaCorreccion.value || null,
            asignaciones: asignacionesEditables.value.map((a) => ({
                partida_id: a.partidaId,
                empleado_id: a.empleadoId,
                dia_semana: a.dia,
                horas_trabajadas: a.horas,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editando.value = false;
            },
            onError: (errores) => {
                errorCorreccion.value = (Object.values(errores)[0] as string) ?? 'No se pudo guardar la corrección.';
            },
            onFinish: () => {
                guardandoCorreccion.value = false;
            },
        },
    );
}

// ---------- Notas / observaciones por empleado (agregar una nueva) ----------

const modalNotasAbierto = ref(false);
const asignacionNotas = ref<{ id: number; empleadoNombre: string; partidaDescripcion: string } | null>(null);
const textoNuevaNota = ref('');
const guardandoNota = ref(false);
const errorNota = ref<string | null>(null);

function abrirNotas(asig: EmpleadoAsignado, partidaDescripcion: string): void {
    asignacionNotas.value = { id: asig.id, empleadoNombre: asig.empleado.nombre, partidaDescripcion };
    textoNuevaNota.value = '';
    errorNota.value = null;
    modalNotasAbierto.value = true;
}

function guardarNota(): void {
    if (!asignacionNotas.value) return;

    if (!textoNuevaNota.value.trim()) {
        errorNota.value = 'Escribe una observación.';
        return;
    }

    guardandoNota.value = true;
    errorNota.value = null;

    router.post(
        `/planeacion/${props.planeacion.id}/asignaciones/${asignacionNotas.value.id}/incidencias`,
        { tipo: 'otro', notas: textoNuevaNota.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalNotasAbierto.value = false;
            },
            onError: (errores) => {
                errorNota.value = (Object.values(errores)[0] as string) ?? 'No se pudo guardar la observación.';
            },
            onFinish: () => {
                guardandoNota.value = false;
            },
        },
    );
}

// ---------- Enviar / Aprobar / Rechazar: URLs literales contra routes/planeacion.php ----------

const procesando = ref(false);

function enviar(): void {
    procesando.value = true;
    router.post(
        `/planeacion/${props.planeacion.id}/enviar`,
        {},
        {
            preserveScroll: true,
            onError: (errores) => {
                alert((Object.values(errores)[0] as string) ?? 'No se pudo enviar la planeación.');
            },
            onFinish: () => (procesando.value = false),
        },
    );
}

const modalAprobarAbierto = ref(false);
const comentarioAprobacion = ref('');

function abrirAprobar(): void {
    comentarioAprobacion.value = '';
    modalAprobarAbierto.value = true;
}

function confirmarAprobacion(): void {
    procesando.value = true;
    router.post(
        `/planeacion/${props.planeacion.id}/aprobar`,
        { comentarios: comentarioAprobacion.value || null },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalAprobarAbierto.value = false;
            },
            onFinish: () => (procesando.value = false),
        },
    );
}

const modalRechazoAbierto = ref(false);
const comentarioRechazo = ref('');
const errorRechazo = ref<string | null>(null);

function abrirRechazo(): void {
    comentarioRechazo.value = '';
    errorRechazo.value = null;
    modalRechazoAbierto.value = true;
}

function confirmarRechazo(): void {
    if (!comentarioRechazo.value.trim()) {
        errorRechazo.value = 'El comentario es obligatorio para rechazar.';
        return;
    }

    procesando.value = true;
    router.post(
        `/planeacion/${props.planeacion.id}/rechazar`,
        { comentarios: comentarioRechazo.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalRechazoAbierto.value = false;
            },
            onError: (errores) => {
                errorRechazo.value = (Object.values(errores)[0] as string) ?? 'No se pudo rechazar.';
            },
            onFinish: () => (procesando.value = false),
        },
    );
}
</script>

<template>

    <Head :title="`Planeación · Semana ${planeacion.semana}/${planeacion.anio}`" />

    <PageLayout :title="`Semana ${planeacion.semana} / ${planeacion.anio}`"
        :description="`${planeacion.fechaInicio} — ${planeacion.fechaFin}`">
        <template #breadcrumbs>
            <Link :href="PlaneacionController.index()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <template #actions>
            <Badge :class="estadoConfig[planeacion.estado].badge" class="gap-1.5 text-xs font-medium">
                <component :is="estadoConfig[planeacion.estado].icon" class="size-3.5" />
                {{ estadoConfig[planeacion.estado].label }}
            </Badge>

            <template v-if="!editando">
                <Button v-if="planeacion.puedeEnviar" size="sm" :disabled="procesando || planeacion.corte?.vencido"
                    @click="enviar">
                    <Send class="mr-1.5 size-4" />
                    Enviar a aprobación
                </Button>

                <template v-if="props.puedeAprobar && planeacion.estado === 'enviada'">
                    <Button variant="outline" size="sm" class="border-red-300 text-red-600 hover:bg-red-50"
                        :disabled="procesando" @click="abrirRechazo">
                        <XCircle class="mr-1.5 size-4" />
                        Rechazar
                    </Button>
                    <Button size="sm" class="bg-emerald-600 text-white hover:bg-emerald-700" :disabled="procesando"
                        @click="abrirAprobar">
                        <CheckCircle2 class="mr-1.5 size-4" />
                        Aprobar
                    </Button>
                </template>
            </template>
        </template>

        <div class="space-y-5">
            <!-- ================= Encabezado de contexto (compacto) ================= -->
            <div class="grid gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-start gap-2">
                    <Building2 class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Planta</p>
                        <p class="truncate text-sm font-semibold">{{ planeacion.planta.nombre }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <FolderOpen class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Proyecto</p>
                        <p class="truncate text-sm font-semibold">{{ planeacion.proyecto.nombre }}</p>
                        <p class="text-xs text-muted-foreground">{{ planeacion.proyecto.folio }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <UserIcon class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Residente</p>
                        <p class="truncate text-sm font-semibold">{{ planeacion.residente.nombre ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <CalendarIcon class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Horas / Empleados</p>
                        <p class="text-sm font-semibold">{{ totalHoras.toFixed(1) }}h · {{ totalEmpleados }} personas
                        </p>
                    </div>
                </div>
            </div>

            <!-- Corte de entrega -->
            <div v-if="planeacion.corte && !editando" class="flex items-start gap-3 rounded-2xl border p-4 shadow-sm"
                :class="planeacion.corte.vencido
                    ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/20'
                    : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20'">
                <AlertCircle class="mt-0.5 size-5 shrink-0"
                    :class="planeacion.corte.vencido ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold"
                        :class="planeacion.corte.vencido ? 'text-red-800 dark:text-red-400' : 'text-amber-800 dark:text-amber-400'">
                        {{ planeacion.corte.vencido ? 'Corte de entrega vencido' : 'Corte de entrega' }}
                    </p>
                    <p class="mt-0.5 text-sm"
                        :class="planeacion.corte.vencido ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300'">
                        {{ planeacion.corte.vencido
                            ? `Venció el ${planeacion.corte.fecha}. Contacta a tu supervisor si necesitas enviarla.`
                            : `Debes enviarla antes del ${planeacion.corte.fecha}.` }}
                    </p>
                </div>
            </div>

            <!-- ================= Banner de rechazo: bloqueante, con la corrección integrada ================= -->
            <div v-if="planeacion.estado === 'rechazada' && !editando"
                class="overflow-hidden rounded-2xl border-2 border-red-300 bg-red-50 shadow-sm dark:border-red-800 dark:bg-red-950/30">
                <div class="flex items-start gap-3 px-5 py-4">
                    <AlertCircle class="mt-0.5 size-6 shrink-0 text-red-600 dark:text-red-400" />
                    <div class="min-w-0 flex-1">
                        <p class="text-base font-bold text-red-800 dark:text-red-400">
                            Esta planeación fue rechazada
                        </p>
                        <p v-if="planeacion.comentariosAprobacion" class="mt-1 text-sm text-red-700 dark:text-red-300">
                            {{ planeacion.comentariosAprobacion }}
                        </p>
                        <p v-if="planeacion.fechaRechazo" class="mt-1 text-xs text-red-600/80 dark:text-red-400/70">
                            Rechazada el {{ planeacion.fechaRechazo }} por {{ planeacion.aprobador ?? '—' }}
                        </p>
                    </div>
                </div>

                <div v-if="planeacion.puedeEditar"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-red-200 bg-red-100/60 px-5 py-3 dark:border-red-900 dark:bg-red-950/40">
                    <p class="text-sm font-medium text-red-800 dark:text-red-300">
                        Corrige lo señalado y vuelve a enviarla a aprobación.
                    </p>
                    <Button size="sm" class="shrink-0 bg-red-600 text-white hover:bg-red-700" @click="iniciarEdicion">
                        <Pencil class="mr-1.5 size-4" />
                        Editar y Corregir Planeación
                    </Button>
                </div>
            </div>

            <!-- También permite corregir un borrador ya iniciado, sin esperar rechazo -->
            <div v-if="planeacion.estado === 'borrador' && planeacion.puedeEditar && !editando"
                class="flex items-center justify-between gap-3 rounded-2xl border bg-card px-5 py-3 shadow-sm">
                <p class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Pencil class="size-4" />
                    Esta planeación sigue en borrador.
                </p>
                <Button size="sm" variant="outline" @click="iniciarEdicion">
                    <Pencil class="mr-1.5 size-4" />
                    Editar cronograma
                </Button>
            </div>

            <!-- ================= CRONOGRAMA: elemento principal de la vista ================= -->

            <!-- Modo lectura -->
            <div v-if="!editando" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/30 px-5 py-4">
                    <div>
                        <p class="text-base font-semibold">Cronograma de trabajo</p>
                        <p class="text-xs text-muted-foreground">{{ planeacion.fechaInicio }} — {{ planeacion.fechaFin
                        }}</p>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Haz clic en un empleado para agregarle una observación
                    </p>
                </div>

                <div v-if="!cronograma.length" class="p-12 text-center text-sm text-muted-foreground">
                    Esta planeación no tiene actividades asignadas.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[900px] border-collapse text-sm">
                        <thead>
                            <tr class="bg-muted/50">
                                <th class="w-64 border-b px-4 py-3 text-left font-semibold">Actividad</th>
                                <th v-for="dia in DIAS_ENUM" :key="dia"
                                    class="border-b border-l px-2 py-3 text-center font-semibold">
                                    {{ DIAS_LABEL[dia] }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="grupo in cronograma" :key="grupo.partida.id" class="align-top">
                                <td class="border-b px-4 py-3">
                                    <p class="font-medium leading-snug">{{ grupo.partida.descripcion }}</p>
                                    <p v-if="grupo.partida.unidad" class="text-xs text-muted-foreground">
                                        {{ grupo.partida.cantidad }} {{ grupo.partida.unidad }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ grupo.horasTotal.toFixed(1) }}h totales
                                    </p>
                                </td>
                                <td v-for="dia in DIAS_ENUM" :key="dia"
                                    class="min-w-[130px] border-b border-l p-2 align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <button v-for="asig in grupo.dias.find((d) => d.dia === dia)?.empleados ?? []"
                                            :key="asig.id" type="button"
                                            class="flex w-full items-center gap-1.5 rounded-md bg-primary/10 px-2 py-1.5 text-left transition-colors hover:bg-primary/20"
                                            :title="asig.incidencias.length ? 'Ver / agregar observación' : 'Agregar observación'"
                                            @click="abrirNotas(asig, grupo.partida.descripcion)">
                                            <span
                                                class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/20 text-[10px] font-bold text-primary">
                                                {{ asig.empleado.nombre.charAt(0) }}
                                            </span>
                                            <span class="min-w-0 flex-1 truncate text-xs font-medium">{{
                                                asig.empleado.nombre.split(' ')[0] }}</span>
                                            <span class="shrink-0 text-xs text-muted-foreground">{{
                                                asig.horasTrabajadas }}h</span>
                                            <AlertCircle v-if="asig.incidencias.length"
                                                class="size-3.5 shrink-0 text-amber-500" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modo edición -->
            <div v-else class="space-y-4">
                <div
                    class="flex items-center justify-between rounded-2xl border bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-400">
                    <span class="flex items-center gap-2">
                        <Pencil class="size-4" />
                        Corrigiendo cronograma — los cambios no se guardan hasta que presiones "Guardar" o "Reenviar".
                    </span>
                    <Button variant="ghost" size="sm" class="h-7 text-xs" @click="cancelarEdicion">
                        <X class="mr-1 size-3.5" />
                        Cancelar
                    </Button>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold">Empleados disponibles</p>
                        <div class="flex items-center gap-3 text-[11px] text-muted-foreground">
                            <span>{{ totalHorasEdicion.toFixed(1) }}h programadas</span>
                        </div>
                    </div>

                    <div class="mb-4 rounded-xl border bg-muted/30 p-3">
                        <p class="mb-2 flex items-center gap-1.5 text-[11px] font-medium text-muted-foreground">
                            <UserIcon class="size-3.5" />
                            Arrastra un empleado sobre una celda para asignarlo. También puedes arrastrar un chip ya
                            puesto hacia otra celda para moverlo.
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

                    <div class="overflow-x-auto rounded-xl border">
                        <table class="w-full min-w-[900px] border-collapse text-xs">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th class="w-56 border-b px-3 py-2 text-left font-semibold">Actividad</th>
                                    <th v-for="dia in DIAS_ENUM" :key="dia"
                                        class="border-b border-l px-2 py-2 text-center font-semibold">
                                        {{ DIAS_LABEL[dia] }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="partida in partidasParaEdicion" :key="partida.id" class="align-top">
                                    <td class="border-b px-3 py-2">
                                        <p class="font-medium leading-snug">{{ partida.descripcion }}</p>
                                    </td>
                                    <td v-for="dia in DIAS_ENUM" :key="dia"
                                        class="min-w-[110px] border-b border-l p-1 align-top" @dragover.prevent
                                        @drop="onDropCelda($event, partida.id, partida.descripcion, dia)">
                                        <div
                                            class="flex min-h-[52px] flex-col gap-1 rounded-lg border border-dashed border-transparent p-1 transition-colors hover:border-primary/40 hover:bg-primary/5">
                                            <div v-for="a in celdaEditable(partida.id, dia)" :key="a.id"
                                                draggable="true" @dragstart="onDragStartAsignacion($event, a)"
                                                class="flex cursor-grab items-center gap-1 rounded-md bg-primary/10 px-1.5 py-1 active:cursor-grabbing">
                                                <span class="min-w-0 flex-1 truncate font-medium text-primary">{{
                                                    a.empleadoNombre.split(' ')[0] }}</span>
                                                <Input type="number" v-model.number="a.horas" min="0.5" max="24"
                                                    step="0.5"
                                                    class="h-6 w-12 border-none bg-background px-1 text-center text-[11px]" />
                                                <button type="button"
                                                    class="shrink-0 text-muted-foreground hover:text-destructive"
                                                    @click="quitarAsignacionEditable(a.id)">
                                                    <Trash2 class="size-3" />
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Nota opcional al reenviar -->
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <button type="button" class="flex w-full items-center justify-between text-left text-sm font-medium"
                        @click="notaVisible = !notaVisible">
                        <span class="flex items-center gap-2">
                            <MessageSquareText class="size-4 text-muted-foreground" />
                            Nota para el supervisor (opcional)
                        </span>
                        <ChevronDown class="size-4 transition-transform" :class="notaVisible ? 'rotate-180' : ''" />
                    </button>
                    <div v-if="notaVisible" class="mt-3">
                        <Textarea v-model="notaCorreccion" rows="2"
                            placeholder="Ej. Ajusté las horas del jueves y agregué a otro empleado el viernes." />
                    </div>
                </div>

                <div
                    class="flex flex-col items-end gap-2 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-end">
                    <p v-if="errorCorreccion" class="mr-auto text-xs text-destructive">{{ errorCorreccion }}</p>
                    <Button variant="outline" :disabled="guardandoCorreccion" @click="guardarCorreccion(false)">
                        Guardar cambios
                    </Button>
                    <Button :disabled="guardandoCorreccion" @click="guardarCorreccion(true)">
                        <Send class="mr-2 size-4" />
                        Guardar y (re)enviar a aprobación
                    </Button>
                </div>
            </div>

            <!-- ================= Notas separadas: Planeación vs. por empleado ================= -->
            <div v-if="!editando" class="grid gap-4 lg:grid-cols-2">
                <!-- Notas de la Planeación: comentario de aprobación / rechazo -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <MessageSquareText class="size-4 text-muted-foreground" />
                        <p class="text-sm font-semibold">Notas de la Planeación</p>
                    </div>

                    <div v-if="planeacion.comentariosAprobacion" class="space-y-2">
                        <div class="rounded-xl border px-4 py-3" :class="planeacion.estado === 'rechazada'
                            ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/20'
                            : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20'">
                            <div class="flex items-center gap-2">
                                <Badge :class="planeacion.estado === 'rechazada'
                                    ? 'bg-red-500/10 text-red-600'
                                    : 'bg-emerald-500/10 text-emerald-600'" class="text-[10px] uppercase">
                                    {{ planeacion.estado === 'rechazada' ? 'Rechazo' : 'Aprobación' }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">{{ planeacion.aprobador ?? '—' }}</span>
                            </div>
                            <p class="mt-2 text-sm">{{ planeacion.comentariosAprobacion }}</p>
                            <p class="mt-1 text-[11px] text-muted-foreground">
                                {{ planeacion.estado === 'rechazada' ? planeacion.fechaRechazo :
                                planeacion.fechaAprobacion }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">Sin notas de revisión todavía.</p>
                </div>

                <!-- Observaciones por empleado: cada una con nombre + actividad + día -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <Users class="size-4 text-muted-foreground" />
                        <p class="text-sm font-semibold">Observaciones por empleado</p>
                    </div>

                    <div v-if="observaciones.length" class="max-h-72 space-y-2 overflow-y-auto pr-1">
                        <div v-for="obs in observaciones" :key="obs.id" class="rounded-xl border bg-muted/30 px-3 py-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/15 text-[10px] font-bold text-primary">
                                    {{ obs.empleadoNombre.charAt(0) }}
                                </span>
                                <span class="text-sm font-semibold">{{ obs.empleadoNombre }}</span>
                                <Badge variant="secondary" class="text-[10px]">
                                    {{ obs.partidaDescripcion }} · {{ DIAS_LABEL[obs.dia] }}
                                </Badge>
                                <Badge v-if="obs.tipo !== 'otro'" class="bg-amber-500/10 text-[10px] text-amber-600">
                                    {{ TIPO_INCIDENCIA_LABEL[obs.tipo] ?? obs.tipo }}
                                </Badge>
                            </div>
                            <p v-if="obs.notas" class="mt-1.5 text-sm">{{ obs.notas }}</p>
                            <p v-if="obs.creada" class="mt-1 text-[11px] text-muted-foreground">{{ obs.creada }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">Sin observaciones registradas todavía.</p>
                </div>
            </div>

            <!-- Seguimiento -->
            <div v-if="!editando" class="rounded-2xl border bg-card p-4 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold">Seguimiento</h3>
                <ul class="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2 lg:grid-cols-4">
                    <li v-if="planeacion.fechaEnvio" class="flex justify-between rounded-lg bg-muted/40 px-3 py-2">
                        <span>Enviada</span>
                        <span class="font-medium text-foreground">{{ planeacion.fechaEnvio }}</span>
                    </li>
                    <li v-if="planeacion.fechaAprobacion" class="flex justify-between rounded-lg bg-muted/40 px-3 py-2">
                        <span>Aprobada por {{ planeacion.aprobador ?? '—' }}</span>
                        <span class="font-medium text-foreground">{{ planeacion.fechaAprobacion }}</span>
                    </li>
                    <li v-if="planeacion.fechaRechazo" class="flex justify-between rounded-lg bg-muted/40 px-3 py-2">
                        <span>Rechazada por {{ planeacion.aprobador ?? '—' }}</span>
                        <span class="font-medium text-foreground">{{ planeacion.fechaRechazo }}</span>
                    </li>
                    <li v-if="planeacion.reportadaNomina" class="flex justify-between rounded-lg bg-muted/40 px-3 py-2">
                        <span>Reportada a nómina</span>
                        <span class="font-medium text-foreground">{{ planeacion.fechaReporteNomina }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </PageLayout>

    <!-- Modal: aprobar (comentario opcional) -->
    <Dialog v-model:open="modalAprobarAbierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Aprobar planeación</DialogTitle>
                <DialogDescription>
                    Puedes dejar un comentario opcional; quedará visible en las notas de la Planeación.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="comentario_aprobacion">Comentario (opcional)</Label>
                <Textarea id="comentario_aprobacion" v-model="comentarioAprobacion" rows="3"
                    placeholder="Ej. Todo en orden, aprobado sin observaciones." />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" :disabled="procesando" @click="modalAprobarAbierto = false">Cancelar
                </Button>
                <Button class="bg-emerald-600 text-white hover:bg-emerald-700" :disabled="procesando"
                    @click="confirmarAprobacion">
                    <CheckCircle2 class="mr-2 size-4" />
                    Confirmar aprobación
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modal: rechazo (comentario obligatorio) -->
    <Dialog v-model:open="modalRechazoAbierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Rechazar planeación</DialogTitle>
                <DialogDescription>
                    Indica el motivo del rechazo. El residente lo verá en las notas de su Planeación para corregir.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="comentario_rechazo">Comentario</Label>
                <Textarea id="comentario_rechazo" v-model="comentarioRechazo" rows="4"
                    placeholder="Ej. Faltan horas asignadas para la actividad de soldadura el jueves." />
                <p v-if="errorRechazo" class="text-xs text-destructive">{{ errorRechazo }}</p>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" :disabled="procesando"
                    @click="modalRechazoAbierto = false">Cancelar</Button>
                <Button class="bg-red-600 text-white hover:bg-red-700" :disabled="procesando" @click="confirmarRechazo">
                    Confirmar rechazo
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modal: nueva observación para un empleado -->
    <Dialog v-model:open="modalNotasAbierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Nueva observación</DialogTitle>
                <DialogDescription v-if="asignacionNotas">
                    {{ asignacionNotas.empleadoNombre }} · {{ asignacionNotas.partidaDescripcion }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="nueva_nota">Observación</Label>
                <Textarea id="nueva_nota" v-model="textoNuevaNota" rows="3"
                    placeholder="Ej. Llegó tarde el jueves, se ajustó el horario de salida." />
                <p v-if="errorNota" class="text-xs text-destructive">{{ errorNota }}</p>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" :disabled="guardandoNota" @click="modalNotasAbierto = false">Cancelar
                </Button>
                <Button :disabled="guardandoNota" @click="guardarNota">Guardar observación</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
