<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Calendar as CalendarIcon,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock,
    Download,
    FileText,
    Settings,
    XCircle
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

// --- Tipos Extendidos para el Rediseño ---
interface ProyectoRef { id: number; nombre: string; folio: string; }
interface PlantaRef { id: number; nombre: string; }
type EstadoPlaneacion = 'borrador' | 'enviada' | 'aprobada' | 'rechazada' | 'incidencia';

interface PlaneacionResumen {
    id: number;
    semana: number;
    anio: number;
    estado: EstadoPlaneacion;
    reportadaNomina: boolean;
    proyecto: ProyectoRef | null;
    planta: PlantaRef | null;
    residente: string | null;
    horasProgramadas: number;
    horasDisponibles: number;
    incidencias: string[];
    fechaInicio: string;
    fechaFin: string;
}

const props = defineProps<{
    puedeCrear: boolean;
    puedeEliminar: boolean;
    puedeGestionar: boolean;
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Supervisión de Planeación', href: PlaneacionController.index() }] },
});

// --- Estado de la Vista ---
const vistaActual = ref<'mes' | 'semana' | 'dia'>('mes');
const fechaBase = ref(new Date());

// --- Estado de Selección de Rango ---
const rangoInicio = ref<string | null>(null);
const rangoFin = ref<string | null>(null);

// --- Modales ---
const modalDetalleAbierto = ref(false);
const planeacionSeleccionada = ref<PlaneacionResumen | null>(null);
const motivoRechazo = ref('');

const modalConfigAbierto = ref(false);

// --- Helpers Visuales ---
const estadoConfig: Record<EstadoPlaneacion, { color: string, label: string, bg: string }> = {
    borrador: { color: 'text-gray-500', bg: 'bg-gray-100 dark:bg-gray-800', label: 'Borrador' },
    enviada: { color: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-900/30', label: 'Pendiente' },
    aprobada: { color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30', label: 'Aprobada' },
    rechazada: { color: 'text-red-600', bg: 'bg-red-100 dark:bg-red-900/30', label: 'Rechazada' },
    incidencia: { color: 'text-purple-600', bg: 'bg-purple-100 dark:bg-purple-900/30', label: 'Incidencia' },
};

function porcentajeHoras(prog: number, disp: number) {
    if (!disp) return 0;
    return Math.min((prog / disp) * 100, 100);
}

// --- Lógica de Calendario (Mock para UI) ---
const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const celdasMes = computed(() => {
    // Generación mock de 35 días para demostración visual
    return Array.from({ length: 35 }).map((_, i) => {
        const date = new Date(fechaBase.value.getFullYear(), fechaBase.value.getMonth(), i - 2);
        const iso = date.toISOString().split('T')[0];
        // Mock vinculando planeaciones al día
        const planesDelDia = (props.planeaciones || []).filter(p => iso >= p.fechaInicio && iso <= p.fechaFin);
        return { fecha: date, iso, planes: planesDelDia, currentMonth: date.getMonth() === fechaBase.value.getMonth() };
    });
});

// --- Interacciones ---
function seleccionarDia(iso: string) {
    if (!rangoInicio.value || (rangoInicio.value && rangoFin.value)) {
        rangoInicio.value = iso;
        rangoFin.value = null;
    } else {
        if (iso >= rangoInicio.value) rangoFin.value = iso;
        else { rangoFin.value = rangoInicio.value; rangoInicio.value = iso; }
    }
}

function enRango(iso: string) {
    if (!rangoInicio.value) return false;
    const fin = rangoFin.value || rangoInicio.value;
    return iso >= rangoInicio.value && iso <= fin;
}

function abrirDetalle(plan: PlaneacionResumen) {
    planeacionSeleccionada.value = plan;
    motivoRechazo.value = '';
    modalDetalleAbierto.value = true;
}

// --- Acciones de Negocio ---
function aprobarSeleccion() { /* Lógica de aprobación masiva */ }
function exportarPDF() { /* Lógica para exportar landscape PDF */ }
function rechazarPlan() {
    if (!motivoRechazo.value) return alert('El comentario es obligatorio para rechazar.');
    // Submit...
    modalDetalleAbierto.value = false;
}
</script>

<template>

    <Head title="Supervisión de Planeaciones" />

    <PageLayout title="Planeaciones"
        description="Supervisa, ajusta y aprueba la capacidad operativa de los residentes.">
        <template #actions>
            <div class="flex items-center gap-2">
                <!-- Selector de Vista -->
                <div class="flex items-center rounded-lg border bg-muted/50 p-1">
                    <button v-for="v in ['mes', 'semana', 'dia']" :key="v" @click="vistaActual = v as any"
                        class="rounded-md px-3 py-1.5 text-xs font-medium capitalize transition-all"
                        :class="vistaActual === v ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'">
                        {{ v }}
                    </button>
                </div>
                <Button variant="outline" @click="modalConfigAbierto = true">
                    <Settings class="mr-2 size-4" /> Horarios
                </Button>
            </div>
        </template>

        <Deferred data="planeaciones">
            <template #fallback>
                <div class="h-[600px] animate-pulse rounded-2xl border bg-card/50" />
            </template>

            <!-- Layout Principal: Calendario + Panel Contextual -->
            <div class="grid gap-6 items-start" :class="rangoInicio ? 'lg:grid-cols-[1fr_320px]' : 'grid-cols-1'">

                <!-- CALENDARIO -->
                <div class="flex flex-col rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <!-- Header Navegación -->
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <div class="flex items-center gap-4">
                            <h2 class="text-xl font-semibold tracking-tight">
                                {{fechaBase.toLocaleString('es-MX', { month: 'long', year: 'numeric' }).replace(/^\w/,
                                c =>
                                c.toUpperCase()) }}
                            </h2>
                            <div class="flex items-center gap-1">
                                <Button variant="ghost" size="icon" class="size-8"
                                    @click="fechaBase.setMonth(fechaBase.getMonth() - 1)">
                                    <ChevronLeft class="size-4" />
                                </Button>
                                <Button variant="ghost" size="sm" class="text-xs"
                                    @click="fechaBase = new Date()">Hoy</Button>
                                <Button variant="ghost" size="icon" class="size-8"
                                    @click="fechaBase.setMonth(fechaBase.getMonth() + 1)">
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1.5">
                                <div class="size-2.5 rounded-full bg-emerald-500"></div> Aprobada
                            </span>
                            <span class="flex items-center gap-1.5">
                                <div class="size-2.5 rounded-full bg-amber-500"></div> Pendiente
                            </span>
                            <span class="flex items-center gap-1.5">
                                <div class="size-2.5 rounded-full bg-purple-500"></div> Incidencia
                            </span>
                        </div>
                    </div>

                    <!-- Grid Mes -->
                    <div v-if="vistaActual === 'mes'"
                        class="grid grid-cols-7 border-b bg-muted/30 text-center text-xs font-medium text-muted-foreground">
                        <div v-for="d in diasSemana" :key="d" class="py-2.5 border-r last:border-0">{{ d }}</div>
                    </div>
                    <div v-if="vistaActual === 'mes'" class="grid grid-cols-7 auto-rows-[120px] bg-muted/10">
                        <div v-for="celda in celdasMes" :key="celda.iso" @click="seleccionarDia(celda.iso)"
                            class="group relative border-r border-b p-2 transition-colors cursor-crosshair hover:bg-accent/50"
                            :class="[
                                !celda.currentMonth ? 'bg-muted/30 text-muted-foreground/50' : '',
                                enRango(celda.iso) ? 'bg-primary/5 ring-1 ring-inset ring-primary/20' : ''
                            ]">

                            <p class="text-xs font-medium mb-1"
                                :class="celda.iso === new Date().toISOString().split('T')[0] ? 'flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground' : ''">
                                {{ celda.fecha.getDate() }}
                            </p>

                            <!-- Mini-tarjetas de Planeación -->
                            <div class="flex flex-col gap-1 overflow-y-auto max-h-[80px] scrollbar-hide">
                                <div v-for="plan in celda.planes.slice(0, 3)" :key="plan.id"
                                    @click.stop="abrirDetalle(plan)"
                                    class="flex flex-col gap-0.5 rounded px-1.5 py-1 text-[10px] leading-tight border transition-colors hover:border-primary/50 cursor-pointer"
                                    :class="estadoConfig[plan.estado].bg">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold truncate">{{ plan.residente?.split(' ')[0] }}</span>
                                        <div class="size-1.5 rounded-full"
                                            :class="estadoConfig[plan.estado].color.replace('text-', 'bg-')"></div>
                                    </div>
                                    <div class="w-full bg-background/50 rounded-full h-1 mt-0.5">
                                        <div class="h-full rounded-full bg-primary"
                                            :style="`width: ${porcentajeHoras(plan.horasProgramadas, plan.horasDisponibles)}%`">
                                        </div>
                                    </div>
                                </div>
                                <span v-if="celda.planes.length > 3"
                                    class="text-[10px] text-muted-foreground text-center">
                                    +{{ celda.planes.length - 3 }} más
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL CONTEXTUAL (Aparece al seleccionar rango) -->
                <transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-4">
                    <div v-if="rangoInicio" class="flex flex-col gap-4 sticky top-6">
                        <div class="rounded-2xl border bg-card p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold flex items-center gap-2">
                                    <CalendarIcon class="size-4 text-primary" />
                                    Resumen
                                </h3>
                                <Button variant="ghost" size="icon" class="size-6 text-muted-foreground"
                                    @click="rangoInicio = null; rangoFin = null">
                                    <XCircle class="size-4" />
                                </Button>
                            </div>

                            <p class="text-xs text-muted-foreground mb-4 border-b pb-4">
                                Rango: <strong class="text-foreground">{{ rangoInicio }}</strong> al <strong
                                    class="text-foreground">{{ rangoFin || rangoInicio }}</strong>
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground mb-1">Total Horas Programadas
                                    </p>
                                    <p class="text-2xl font-bold tracking-tight">342<span
                                            class="text-sm font-normal text-muted-foreground">/400 disp.</span></p>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-lg border bg-amber-50/50 p-2 dark:bg-amber-950/20">
                                        <p class="text-xs text-amber-700 dark:text-amber-400">Pendientes</p>
                                        <p class="text-lg font-semibold text-amber-700 dark:text-amber-400">12</p>
                                    </div>
                                    <div class="rounded-lg border bg-purple-50/50 p-2 dark:bg-purple-950/20">
                                        <p class="text-xs text-purple-700 dark:text-purple-400">Incidencias</p>
                                        <p class="text-lg font-semibold text-purple-700 dark:text-purple-400">3</p>
                                    </div>
                                </div>

                                <div class="pt-2 border-t space-y-2">
                                    <Button v-if="props.puedeGestionar"
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white"
                                        @click="aprobarSeleccion">
                                        <CheckCircle2 class="mr-2 size-4" /> Aprobar Pendientes
                                    </Button>
                                    <Button variant="outline" class="w-full" @click="exportarPDF">
                                        <Download class="mr-2 size-4" /> Exportar PDF
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </Deferred>
    </PageLayout>

    <!-- MODAL: Detalle de Planeación -->
    <Dialog v-model:open="modalDetalleAbierto">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <div class="flex items-center justify-between pr-6">
                    <DialogTitle>Detalle de Planeación</DialogTitle>
                    <Badge v-if="planeacionSeleccionada"
                        :class="estadoConfig[planeacionSeleccionada.estado].bg + ' ' + estadoConfig[planeacionSeleccionada.estado].color">
                        {{ estadoConfig[planeacionSeleccionada.estado].label }}
                    </Badge>
                </div>
                <DialogDescription v-if="planeacionSeleccionada">
                    {{ planeacionSeleccionada.fechaInicio }} • {{ planeacionSeleccionada.residente }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="planeacionSeleccionada" class="space-y-6 py-2">
                <!-- Info Contextual -->
                <div class="grid grid-cols-2 gap-4 rounded-xl border bg-muted/30 p-4">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Planta / Proyecto</p>
                        <p class="text-sm font-medium mt-0.5">{{ planeacionSeleccionada.planta?.nombre }}</p>
                        <Link v-if="planeacionSeleccionada.proyecto"
                            :href="`/proyectos/${planeacionSeleccionada.proyecto.id}`"
                            class="text-xs text-primary hover:underline flex items-center mt-1">
                            <FileText class="mr-1 size-3" /> {{ planeacionSeleccionada.proyecto.folio }}
                        </Link>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Desempeño de Horas</p>
                        <div class="mt-1 flex items-end gap-1">
                            <p class="text-xl font-bold">{{ planeacionSeleccionada.horasProgramadas }}</p>
                            <p class="text-sm text-muted-foreground mb-0.5">/ {{ planeacionSeleccionada.horasDisponibles
                                }}h
                            </p>
                        </div>
                        <div class="w-full bg-secondary h-1.5 mt-1 rounded-full overflow-hidden">
                            <div class="h-full bg-primary"
                                :style="`width: ${porcentajeHoras(planeacionSeleccionada.horasProgramadas, planeacionSeleccionada.horasDisponibles)}%`">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Actividades (Mock) -->
                <div>
                    <h4 class="text-sm font-semibold mb-2 flex items-center gap-2">
                        <Clock class="size-4 text-muted-foreground" /> Actividades Planeadas
                    </h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-start border-b pb-2 text-sm">
                            <div>
                                <p class="font-medium">Mantenimiento Preventivo Tableros</p>
                                <p class="text-xs text-muted-foreground">Real vs Planeado detecta desviación de +2h.</p>
                            </div>
                            <span class="font-mono text-muted-foreground">4.5h</span>
                        </div>
                    </div>
                </div>

                <!-- Incidencias -->
                <div v-if="planeacionSeleccionada.incidencias?.length"
                    class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900/50 dark:bg-red-950/20">
                    <h4 class="text-xs font-semibold text-red-800 dark:text-red-400 flex items-center mb-1">
                        <AlertCircle class="mr-1.5 size-3.5" /> Incidencias Reportadas
                    </h4>
                    <ul class="list-disc pl-5 text-xs text-red-700 dark:text-red-300">
                        <li v-for="inc in planeacionSeleccionada.incidencias" :key="inc">{{ inc }}</li>
                    </ul>
                </div>

                <!-- Comentarios (Rechazo) -->
                <div v-if="props.puedeGestionar && planeacionSeleccionada.estado === 'enviada'" class="space-y-2">
                    <Label>Comentario (Obligatorio para rechazar)</Label>
                    <Textarea v-model="motivoRechazo" placeholder="Motivo de desviación o rechazo..." rows="2" />
                </div>
            </div>

            <DialogFooter v-if="props.puedeGestionar && planeacionSeleccionada?.estado === 'enviada'">
                <Button variant="outline" class="border-red-200 text-red-600 hover:bg-red-50" @click="rechazarPlan">
                    Rechazar
                </Button>
                <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="modalDetalleAbierto = false">
                    Aprobar Planeación
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- MODAL: Configuración de Horarios -->
    <Dialog v-model:open="modalConfigAbierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Horarios de Disponibilidad</DialogTitle>
                <DialogDescription>Configura la entrada, salida y días laborales por Residente.</DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <!-- Mock de un selector de residente y su horario -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Hora de Entrada</Label>
                        <Input type="time" value="08:00" />
                    </div>
                    <div class="space-y-2">
                        <Label>Hora de Salida</Label>
                        <Input type="time" value="18:00" />
                    </div>
                </div>
                <p class="text-[11px] text-amber-600 flex items-center bg-amber-50 p-2 rounded border border-amber-100">
                    <AlertCircle class="size-3 mr-1" />
                    Cambiar el horario afectará las métricas de carga en planeaciones futuras.
                </p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="modalConfigAbierto = false">Cancelar</Button>
                <Button @click="modalConfigAbierto = false">Guardar Horario</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
