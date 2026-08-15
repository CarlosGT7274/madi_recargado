<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Building2,
    Calendar as CalendarIcon,
    CheckCircle2,
    Clock,
    FolderOpen,
    Pencil,
    Send,
    Trash2,
    User as UserIcon,
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

const props = defineProps<{
    planeacion: PlaneacionDetalle;
    cronograma: PartidaCronograma[];
    puedeAprobar: boolean;
    empleados: EmpleadoOpcion[];
    partidasDisponibles: PartidaArbolNodo[];
}>();

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

// ---------- Aplanar partidasDisponibles (árbol -> hojas asignables) ----------

interface PartidaPlana {
    id: number;
    descripcion: string;
}

function aplanar(nodos: PartidaArbolNodo[], prefijo = ''): PartidaPlana[] {
    return nodos.flatMap((nodo) => {
        const etiqueta = prefijo === '' ? nodo.nombre : `${prefijo} · ${nodo.nombre}`;
        if (!nodo.hijas.length) {
            return [{ id: nodo.id, descripcion: etiqueta }];
        }
        return aplanar(nodo.hijas, etiqueta);
    });
}

const partidasPlanas = computed<PartidaPlana[]>(() => aplanar(props.partidasDisponibles));

// ---------- Modo edición (solo si puedeEditar) ----------

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
 * Punto de entrada del ciclo "rechazada → editar/corregir → reenviar":
 * carga el cronograma actual como base editable. Disponible tanto para
 * 'borrador' (seguir armando) como 'rechazada' (corregir lo señalado).
 */
function iniciarEdicion(): void {
    asignacionesEditables.value = cronogramaAAsignacionesEditables();
    editando.value = true;
}

function cancelarEdicion(): void {
    editando.value = false;
    asignacionesEditables.value = [];
}

function partidasEnEdicion(): PartidaPlana[] {
    const usadas = new Map<number, string>();
    asignacionesEditables.value.forEach((a) => usadas.set(a.partidaId, a.partidaDescripcion));
    partidasPlanas.value.forEach((p) => usadas.set(p.id, p.descripcion));
    return Array.from(usadas, ([id, descripcion]) => ({ id, descripcion }));
}

function onDragStartEmpleado(evento: DragEvent, empleado: EmpleadoOpcion): void {
    evento.dataTransfer?.setData('text/empleado-id', String(empleado.id));
    evento.dataTransfer!.effectAllowed = 'copy';
}

function onDropCelda(evento: DragEvent, partidaId: number, partidaDescripcion: string, dia: DiaSemana): void {
    evento.preventDefault();
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

const guardandoCorreccion = ref(false);
const errorCorreccion = ref<string | null>(null);

/**
 * Guarda la corrección. `enviarAprobacion` cierra el ciclo completo
 * ("rechazada → corregir → reenviar") en una sola request: el backend
 * regresa la planeación a 'borrador' (limpiando el comentario de
 * rechazo) y, si se pide, la manda a 'enviada' de una vez.
 */
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

// ---------- Totales de solo lectura ----------

const totalHoras = computed(() => props.cronograma.reduce((suma, p) => suma + p.horasTotal, 0));
const totalEmpleados = computed(
    () =>
        new Set(props.cronograma.flatMap((p) => p.dias.flatMap((d) => d.empleados.map((e) => e.empleado.id))))
            .size,
);

const totalHorasEdicion = computed(() => asignacionesEditables.value.reduce((s, a) => s + (Number(a.horas) || 0), 0));

// ---------- Notas / observaciones por empleado ----------
// Reutiliza planeacion_incidencias (tipo 'otro') como bitácora de notas
// libres por asignación (= empleado dentro de esta planeación). No
// depende del estado de la planeación: se puede anotar aunque ya esté
// aprobada, precisamente porque son observaciones de seguimiento, no
// parte del flujo de aprobación.

const modalNotasAbierto = ref(false);
const asignacionNotas = ref<{ id: number; empleadoNombre: string; notas: IncidenciaAsignacion[] } | null>(null);
const textoNuevaNota = ref('');
const guardandoNota = ref(false);
const errorNota = ref<string | null>(null);

function abrirNotas(asig: EmpleadoAsignado): void {
    asignacionNotas.value = {
        id: asig.id,
        empleadoNombre: asig.empleado.nombre,
        notas: asig.incidencias,
    };
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

// ---------- Aprobar / Rechazar ----------

const procesando = ref(false);
const modalRechazoAbierto = ref(false);
const comentarioRechazo = ref('');
const errorRechazo = ref<string | null>(null);

function aprobar(): void {
    procesando.value = true;
    router.post(
        PlaneacionController.aprobar(props.planeacion.id).url,
        {},
        { preserveScroll: true, onFinish: () => (procesando.value = false) },
    );
}

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
        PlaneacionController.rechazar(props.planeacion.id).url,
        { comentarios: comentarioRechazo.value },
        {
            preserveScroll: true,
            onSuccess: () => (modalRechazoAbierto.value = false),
            onError: (errores) => {
                errorRechazo.value = (Object.values(errores)[0] as string) ?? 'No se pudo rechazar.';
            },
            onFinish: () => (procesando.value = false),
        },
    );
}

function enviar(): void {
    procesando.value = true;
    router.post(
        PlaneacionController.enviar(props.planeacion.id).url,
        {},
        {
            preserveScroll: true,
            onError: (errores) => {
                // El backend rechaza el envío si ya venció el corte del residente.
                alert((Object.values(errores)[0] as string) ?? 'No se pudo enviar la planeación.');
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

                <!--
                    Ciclo rechazada -> editar/corregir -> reenviar: mismo botón
                    "Editar / Corregir Planeación" para 'rechazada'. El backend
                    (actualizarCronograma) es quien decide regresar el estado a
                    'borrador' al guardar.
                -->
                <Button v-if="planeacion.puedeEditar && planeacion.estado === 'rechazada'" size="sm" variant="outline"
                    @click="iniciarEdicion">
                    <Pencil class="mr-1.5 size-4" />
                    Editar / Corregir Planeación
                </Button>

                <template v-if="props.puedeAprobar && planeacion.estado === 'enviada'">
                    <Button variant="outline" size="sm" class="border-red-300 text-red-600 hover:bg-red-50"
                        :disabled="procesando" @click="abrirRechazo">
                        <XCircle class="mr-1.5 size-4" />
                        Rechazar
                    </Button>
                    <Button size="sm" class="bg-emerald-600 text-white hover:bg-emerald-700" :disabled="procesando"
                        @click="aprobar">
                        <CheckCircle2 class="mr-1.5 size-4" />
                        Aprobar
                    </Button>
                </template>
            </template>
        </template>

        <!-- Encabezado compacto: contexto de la Planeación -->
        <div class="mb-4 grid gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
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
                    <p class="text-sm font-semibold">{{ totalHoras.toFixed(1) }}h · {{ totalEmpleados }} personas</p>
                </div>
            </div>
        </div>

        <!-- Corte de entrega personalizado del residente -->
        <div v-if="planeacion.corte" class="mb-4 flex items-start gap-3 rounded-2xl border p-4 shadow-sm" :class="planeacion.corte.vencido
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

        <!-- Motivo de rechazo, visible y prominente -->
        <div v-if="planeacion.estado === 'rechazada' && planeacion.comentariosAprobacion"
            class="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-900 dark:bg-red-950/20">
            <AlertCircle class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
            <div class="min-w-0">
                <p class="text-sm font-semibold text-red-800 dark:text-red-400">Planeación rechazada</p>
                <p class="mt-0.5 text-sm text-red-700 dark:text-red-300">{{ planeacion.comentariosAprobacion }}</p>
                <p v-if="planeacion.fechaRechazo" class="mt-1 text-xs text-red-600/80 dark:text-red-400/70">
                    {{ planeacion.fechaRechazo }}
                </p>
            </div>
        </div>

        <!-- ================= CRONOGRAMA: elemento principal ================= -->

        <!-- Modo lectura -->
        <div v-if="!editando" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex items-center justify-between border-b bg-muted/30 px-4 py-3">
                <p class="text-sm font-semibold">Cronograma de trabajo</p>
                <p class="text-xs text-muted-foreground">{{ planeacion.fechaInicio }} — {{ planeacion.fechaFin }}</p>
            </div>

            <div v-if="!cronograma.length" class="p-10 text-center text-sm text-muted-foreground">
                Esta planeación no tiene actividades asignadas.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse text-xs">
                    <thead>
                        <tr class="bg-muted/50">
                            <th class="w-64 border-b px-3 py-2 text-left font-semibold">Actividad</th>
                            <th v-for="dia in DIAS_ENUM" :key="dia"
                                class="border-b border-l px-2 py-2 text-center font-semibold">
                                {{ DIAS_LABEL[dia] }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="grupo in cronograma" :key="grupo.partida.id" class="align-top">
                            <td class="border-b px-3 py-2">
                                <p class="font-medium leading-snug">{{ grupo.partida.descripcion }}</p>
                                <p v-if="grupo.partida.unidad" class="text-[10px] text-muted-foreground">
                                    {{ grupo.partida.cantidad }} {{ grupo.partida.unidad }}
                                </p>
                                <p class="text-[10px] text-muted-foreground">{{ grupo.horasTotal.toFixed(1) }}h totales
                                </p>
                            </td>
                            <td v-for="dia in DIAS_ENUM" :key="dia"
                                class="min-w-[120px] border-b border-l p-1.5 align-top">
                                <div class="flex flex-col gap-1">
                                    <div v-for="asig in grupo.dias.find((d) => d.dia === dia)?.empleados ?? []"
                                        :key="asig.id"
                                        class="flex cursor-pointer items-center gap-1.5 rounded-md bg-primary/10 px-2 py-1 transition-colors hover:bg-primary/20"
                                        :title="asig.incidencias.length ? 'Ver notas' : 'Agregar nota'"
                                        @click="abrirNotas(asig)">
                                        <span
                                            class="flex size-4 shrink-0 items-center justify-center rounded-full bg-primary/20 text-[9px] font-bold text-primary">
                                            {{ asig.empleado.nombre.charAt(0) }}
                                        </span>
                                        <span class="min-w-0 flex-1 truncate font-medium">{{
                                            asig.empleado.nombre.split(' ')[0]
                                            }}</span>
                                        <span class="shrink-0 text-muted-foreground">{{ asig.horasTrabajadas }}h</span>
                                        <AlertCircle v-if="asig.incidencias.length"
                                            class="size-3 shrink-0 text-amber-500" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modo edición: mismo patrón drag&drop que Create.vue, para corregir sin crear una Planeación nueva -->
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
                            <span v-if="empleado.puesto" class="text-muted-foreground">· {{ empleado.puesto }}</span>
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
                            <tr v-for="partida in partidasEnEdicion()" :key="partida.id" class="align-top">
                                <td class="border-b px-3 py-2">
                                    <p class="font-medium leading-snug">{{ partida.descripcion }}</p>
                                </td>
                                <td v-for="dia in DIAS_ENUM" :key="dia"
                                    class="min-w-[110px] border-b border-l p-1 align-top" @dragover.prevent
                                    @drop="onDropCelda($event, partida.id, partida.descripcion, dia)">
                                    <div
                                        class="flex min-h-[52px] flex-col gap-1 rounded-lg border border-dashed border-transparent p-1 transition-colors hover:border-primary/40 hover:bg-primary/5">
                                        <div v-for="a in celdaEditable(partida.id, dia)" :key="a.id"
                                            class="flex items-center gap-1 rounded-md bg-primary/10 px-1.5 py-1">
                                            <span class="min-w-0 flex-1 truncate font-medium text-primary">{{
                                                a.empleadoNombre.split(' ')[0] }}</span>
                                            <Input type="number" v-model.number="a.horas" min="0.5" max="24" step="0.5"
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

            <div
                class="flex flex-col items-end gap-2 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-end">
                <p v-if="errorCorreccion" class="mr-auto text-xs text-destructive">{{ errorCorreccion }}</p>
                <Button variant="outline" :disabled="guardandoCorreccion" @click="guardarCorreccion(false)">
                    Guardar corrección
                </Button>
                <Button :disabled="guardandoCorreccion" @click="guardarCorreccion(true)">
                    <Send class="mr-2 size-4" />
                    Guardar y reenviar a aprobación
                </Button>
            </div>
        </div>

        <!-- Seguimiento -->
        <div v-if="!editando" class="mt-4 rounded-2xl border bg-card p-4 shadow-sm">
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
    </PageLayout>

    <!-- Modal de rechazo -->
    <Dialog v-model:open="modalRechazoAbierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Rechazar planeación</DialogTitle>
                <DialogDescription>
                    Indica el motivo del rechazo. El residente lo verá para hacer las correcciones necesarias.
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

    <!-- Modal de notas por empleado -->
    <Dialog v-model:open="modalNotasAbierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Notas · {{ asignacionNotas?.empleadoNombre }}</DialogTitle>
                <DialogDescription>
                    Observaciones sobre esta asignación. Se guardan sin importar el estado de la planeación.
                </DialogDescription>
            </DialogHeader>

            <div v-if="asignacionNotas" class="space-y-4">
                <div v-if="asignacionNotas.notas.length" class="max-h-48 space-y-2 overflow-y-auto pr-1">
                    <div v-for="nota in asignacionNotas.notas" :key="nota.id"
                        class="rounded-lg border bg-muted/30 px-3 py-2 text-sm">
                        <p class="whitespace-pre-wrap">{{ nota.notas ?? '—' }}</p>
                        <p v-if="nota.creada" class="mt-1 text-[11px] text-muted-foreground">{{ nota.creada }}</p>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">Sin notas registradas todavía.</p>

                <div class="space-y-2">
                    <Label for="nueva_nota">Nueva observación</Label>
                    <Textarea id="nueva_nota" v-model="textoNuevaNota" rows="3"
                        placeholder="Ej. Llegó tarde el jueves, se ajustó el horario de salida." />
                    <p v-if="errorNota" class="text-xs text-destructive">{{ errorNota }}</p>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" :disabled="guardandoNota" @click="modalNotasAbierto = false">Cerrar</Button>
                <Button :disabled="guardandoNota" @click="guardarNota">Guardar nota</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
