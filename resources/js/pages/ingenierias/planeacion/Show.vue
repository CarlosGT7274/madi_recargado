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
    Layers,
    User as UserIcon,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type EstadoPlaneacion = 'borrador' | 'enviada' | 'aprobada' | 'rechazada';
type DiaSemana = 'lunes' | 'martes' | 'miercoles' | 'jueves' | 'viernes' | 'sabado' | 'domingo';

interface PlantaRef {
    id: number;
    nombre: string;
}

interface ProyectoRef {
    id: number;
    nombre: string;
    folio: string;
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
    puedeEnviar: boolean;
    puedeEliminar: boolean;
}

interface EmpleadoAsignado {
    id: number;
    empleado: { id: number; nombre: string };
    diaSemana: DiaSemana;
    estado: string;
    horasTrabajadas: number;
    horasExtra: number;
    incidencias: { id: number; tipo: string; notas: string | null; creada: string | null }[];
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

const props = defineProps<{
    planeacion: PlaneacionDetalle;
    cronograma: PartidaCronograma[];
    puedeAprobar: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Planeación', href: PlaneacionController.index() },
            { title: 'Detalle', href: '' },
        ],
    },
});

const DIAS_LABEL: Record<DiaSemana, string> = {
    lunes: 'Lunes',
    martes: 'Martes',
    miercoles: 'Miércoles',
    jueves: 'Jueves',
    viernes: 'Viernes',
    sabado: 'Sábado',
    domingo: 'Domingo',
};

const estadoConfig: Record<EstadoPlaneacion, { label: string; badge: string; icon: typeof CheckCircle2 }> = {
    borrador: { label: 'Borrador', badge: 'bg-muted text-muted-foreground', icon: Clock },
    enviada: { label: 'Pendiente de aprobación', badge: 'bg-amber-500/10 text-amber-600', icon: Clock },
    aprobada: { label: 'Aprobada', badge: 'bg-emerald-500/10 text-emerald-600', icon: CheckCircle2 },
    rechazada: { label: 'Rechazada', badge: 'bg-red-500/10 text-red-600', icon: XCircle },
};

const totalHoras = props.cronograma.reduce((suma, p) => suma + p.horasTotal, 0);
const totalEmpleados = new Set(
    props.cronograma.flatMap((p) => p.dias.flatMap((d) => d.empleados.map((e) => e.empleado.id))),
).size;

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
        {
            preserveScroll: true,
            onFinish: () => (procesando.value = false),
        },
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
        { preserveScroll: true, onFinish: () => (procesando.value = false) },
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

            <Button v-if="planeacion.puedeEnviar" size="sm" :disabled="procesando" @click="enviar">
                Enviar a aprobación
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

        <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
            <!-- Cronograma -->
            <div class="space-y-4">
                <div v-if="!cronograma.length"
                    class="rounded-2xl border border-dashed bg-card/50 p-10 text-center text-sm text-muted-foreground">
                    Esta planeación no tiene actividades asignadas.
                </div>

                <div v-for="grupo in cronograma" :key="grupo.partida.id" class="rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between gap-2 border-b bg-muted/30 px-4 py-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <Layers class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ grupo.partida.descripcion }}</p>
                                <p v-if="grupo.partida.unidad" class="text-xs text-muted-foreground">
                                    {{ grupo.partida.cantidad }} {{ grupo.partida.unidad }}
                                </p>
                            </div>
                        </div>
                        <Badge variant="secondary" class="shrink-0 text-xs">{{ grupo.horasTotal.toFixed(1) }}h</Badge>
                    </div>

                    <div class="divide-y">
                        <div v-for="dia in grupo.dias" :key="dia.dia"
                            class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-start">
                            <div class="w-28 shrink-0">
                                <p class="text-xs font-semibold uppercase text-muted-foreground">{{ DIAS_LABEL[dia.dia]
                                    }}</p>
                                <p class="text-[11px] text-muted-foreground">{{ dia.horasTotal.toFixed(1) }}h totales
                                </p>
                            </div>

                            <div class="flex flex-1 flex-wrap gap-2">
                                <div v-for="asig in dia.empleados" :key="asig.id"
                                    class="flex items-center gap-2 rounded-full border bg-primary/5 px-3 py-1.5 text-xs">
                                    <span
                                        class="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                                        {{ asig.empleado.nombre.charAt(0) }}
                                    </span>
                                    <span class="font-medium">{{ asig.empleado.nombre }}</span>
                                    <span class="text-muted-foreground">· {{ asig.horasTrabajadas }}h</span>
                                    <span v-if="asig.horasExtra > 0" class="text-amber-600">+{{ asig.horasExtra }}h
                                        extra</span>
                                    <AlertCircle v-if="asig.incidencias.length" class="size-3 text-amber-500"
                                        :title="`${asig.incidencias.length} incidencia(s)`" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="space-y-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                        <CalendarIcon class="size-4 text-primary" />
                        Resumen
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-2">
                            <Building2 class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Planta</p>
                                <p class="font-medium">{{ planeacion.planta.nombre }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <FolderOpen class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Proyecto</p>
                                <p class="font-medium">{{ planeacion.proyecto.nombre }}</p>
                                <p class="text-xs text-muted-foreground">{{ planeacion.proyecto.folio }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <UserIcon class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Residente</p>
                                <p class="font-medium">{{ planeacion.residente.nombre ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 border-t pt-3">
                            <div class="rounded-lg bg-muted/40 p-2 text-center">
                                <p class="text-lg font-bold">{{ totalHoras.toFixed(0) }}h</p>
                                <p class="text-[10px] uppercase text-muted-foreground">Programadas</p>
                            </div>
                            <div class="rounded-lg bg-muted/40 p-2 text-center">
                                <p class="text-lg font-bold">{{ totalEmpleados }}</p>
                                <p class="text-[10px] uppercase text-muted-foreground">Empleados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold">Seguimiento</h3>
                    <ul class="space-y-2 text-xs text-muted-foreground">
                        <li v-if="planeacion.fechaEnvio" class="flex justify-between">
                            <span>Enviada</span>
                            <span class="font-medium text-foreground">{{ planeacion.fechaEnvio }}</span>
                        </li>
                        <li v-if="planeacion.fechaAprobacion" class="flex justify-between">
                            <span>Aprobada por {{ planeacion.aprobador ?? '—' }}</span>
                            <span class="font-medium text-foreground">{{ planeacion.fechaAprobacion }}</span>
                        </li>
                        <li v-if="planeacion.fechaRechazo" class="flex justify-between">
                            <span>Rechazada por {{ planeacion.aprobador ?? '—' }}</span>
                            <span class="font-medium text-foreground">{{ planeacion.fechaRechazo }}</span>
                        </li>
                        <li v-if="planeacion.reportadaNomina" class="flex justify-between">
                            <span>Reportada a nómina</span>
                            <span class="font-medium text-foreground">{{ planeacion.fechaReporteNomina }}</span>
                        </li>
                    </ul>

                    <p v-if="planeacion.comentariosAprobacion" class="mt-3 rounded-lg border px-3 py-2 text-xs"
                        :class="planeacion.estado === 'rechazada'
                            ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/20 dark:text-red-400'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-400'">
                        {{ planeacion.comentariosAprobacion }}
                    </p>
                </div>
            </div>
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
</template>
