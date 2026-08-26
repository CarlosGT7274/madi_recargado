<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, ClipboardList, FilePen, Plus, Send, XCircle } from '@lucide/vue';
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
    fechaInicio: string;
    fechaFin: string;
    fechaEnvio: string | null;
    fechaAprobacion: string | null;
    fechaRechazo: string | null;
    comentariosAprobacion: string | null;
}

// ---------- Compromisos de entrega del usuario actual ----------
// Ahora puede tener varios (compromisos_entrega), cada uno opcionalmente
// ligado a una planta o a un proyecto puntual. El controller ya los manda
// resueltos y ordenados por fecha vía Inertia::defer, igual que
// `planeaciones` — no hace falta pedirlos aparte.

interface CompromisoUsuario {
    id: number;
    /** ISO datetime */
    fecha: string;
    nota: string | null;
    vencido: boolean;
    planta: { id: number; nombre: string } | null;
    proyecto: { id: number; nombre: string; folio: string } | null;
}

const props = defineProps<{
    puedeCrear: boolean;
    puedeEliminar: boolean;
    compromisos?: CompromisoUsuario[];
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mis Planeaciones', href: PlaneacionController.index() }],
    },
});

function compromisoVencido(c: CompromisoUsuario): boolean {
    return c.vencido;
}

function formatCompromisoLargo(fechaIso: string): string {
    const fecha = new Date(fechaIso);
    const dia = fecha.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
    const hora = fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    return `${dia} a las ${hora}`;
}

function cuentaRegresiva(fechaIso: string): string {
    const diffMs = new Date(fechaIso).getTime() - Date.now();
    if (diffMs <= 0) return 'Vencida';

    const dias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const horas = Math.floor((diffMs / (1000 * 60 * 60)) % 24);
    if (dias > 0) return `Vence en ${dias} d ${horas} h`;
    const minutos = Math.floor((diffMs / (1000 * 60)) % 60);
    return `Vence en ${horas} h ${minutos} min`;
}

function alcanceLabel(c: CompromisoUsuario): string {
    if (c.proyecto) return c.proyecto.nombre;
    if (c.planta) return c.planta.nombre;
    return 'Compromiso general';
}

/**
 * Ordena: primero los vencidos (más urgente arriba), luego los
 * pendientes por fecha más próxima.
 */
const compromisosOrdenados = computed<CompromisoUsuario[]>(() => {
    const lista = props.compromisos ?? [];
    return [...lista].sort((a, b) => {
        if (a.vencido !== b.vencido) return a.vencido ? -1 : 1;
        return a.fecha.localeCompare(b.fecha);
    });
});

/**
 * Click en un compromiso -> ir a Nueva Planeación con el proyecto/planta
 * de ese compromiso preseleccionado (si tenía uno; si es general, solo
 * navega a Crear normal). El formulario sigue siendo editable, esto solo
 * ahorra tener que volver a elegir planta/proyecto a mano.
 * Nota: Create.vue debe leer `planta_id`/`proyecto_id` de la query string
 * para poder preseleccionarlos en su paso 1 (mismo patrón que ya usa para
 * `anio`/`semana` desde el calendario del supervisor).
 */
function irACrearDesdeCompromiso(c: CompromisoUsuario): void {
    const query: Record<string, number> = {};
    if (c.planta) query.planta = c.planta.id;
    if (c.proyecto) query.proyecto = c.proyecto.id;

    router.visit(PlaneacionController.create({ query }).url);
}

// ---------- Tabs / listado ----------

type Tab = 'todas' | 'enviada' | 'aprobada' | 'rechazada' | 'borrador';

const tabActiva = ref<Tab>('todas');

const tabs: { value: Tab; label: string }[] = [
    { value: 'todas', label: 'Todas' },
    { value: 'borrador', label: 'Borradores' },
    { value: 'enviada', label: 'En revisión' },
    { value: 'aprobada', label: 'Aprobadas' },
    { value: 'rechazada', label: 'Rechazadas' },
];

function contar(lista: PlaneacionResumen[], estado: Tab): number {
    if (estado === 'todas') return lista.length;
    return lista.filter((p) => p.estado === estado).length;
}

function filtradas(lista: PlaneacionResumen[]): PlaneacionResumen[] {
    if (tabActiva.value === 'todas') return lista;
    return lista.filter((p) => p.estado === tabActiva.value);
}

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

function enviar(id: number): void {
    router.post(PlaneacionController.enviar(id).url, {}, { preserveScroll: true });
}

function eliminar(id: number): void {
    if (!confirm('¿Eliminar esta planeación? Esta acción no se puede deshacer.')) return;
    router.delete(PlaneacionController.destroy(id).url, { preserveScroll: true });
}
</script>

<template>

    <Head title="Mis Planeaciones" />

    <PageLayout title="Mis Planeaciones" description="Captura, envía y da seguimiento a tus planeaciones semanales">
        <template #actions>
            <Link v-if="props.puedeCrear" :href="PlaneacionController.create()">
                <Button>
                    <Plus class="mr-2 size-4" />
                    Nueva Planeación
                </Button>
            </Link>
        </template>

        <!-- Compromisos de entrega pendientes: uno o varios, cada uno clicable para prellenar Crear -->
        <Deferred data="compromisos">
            <template #fallback>
                <div class="mb-6 h-20 animate-pulse rounded-2xl border bg-card/50" />
            </template>

            <div v-if="compromisosOrdenados.length" class="mb-6 space-y-2">
                <button v-for="c in compromisosOrdenados" :key="c.id" type="button"
                    class="flex w-full items-start gap-3 rounded-2xl border p-4 text-left shadow-sm transition-colors"
                    :class="compromisoVencido(c)
                        ? 'border-red-200 bg-red-50 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/20 dark:hover:bg-red-950/30'
                        : 'border-amber-200 bg-amber-50 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/20 dark:hover:bg-amber-950/30'"
                    @click="irACrearDesdeCompromiso(c)">
                    <AlertCircle class="mt-0.5 size-5 shrink-0"
                        :class="compromisoVencido(c) ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold"
                                :class="compromisoVencido(c) ? 'text-red-800 dark:text-red-400' : 'text-amber-800 dark:text-amber-400'">
                                {{ compromisoVencido(c) ? 'Compromiso vencido' : 'Compromiso de entrega pendiente' }}
                                · {{ alcanceLabel(c) }}
                            </p>
                            <Badge
                                :class="compromisoVencido(c) ? 'bg-red-500/10 text-red-600' : 'bg-amber-500/10 text-amber-600'"
                                class="text-[10px] font-medium">
                                {{ cuentaRegresiva(c.fecha) }}
                            </Badge>
                        </div>
                        <p class="mt-0.5 text-sm"
                            :class="compromisoVencido(c) ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300'">
                            {{ formatCompromisoLargo(c.fecha) }}
                        </p>
                        <p v-if="c.nota" class="mt-1.5 text-xs"
                            :class="compromisoVencido(c) ? 'text-red-700/80 dark:text-red-300/80' : 'text-amber-700/80 dark:text-amber-300/80'">
                            Nota de tu supervisor: {{ c.nota }}
                        </p>
                        <p class="mt-1.5 text-[11px] font-medium underline-offset-2 group-hover:underline"
                            :class="compromisoVencido(c) ? 'text-red-700/70' : 'text-amber-700/70'">
                            Toca para crear la planeación con estos datos precargados
                        </p>
                    </div>
                </button>
            </div>
        </Deferred>

        <Deferred data="planeaciones">
            <template #fallback>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-2xl border bg-card/50" />
                </div>
            </template>

            <div v-if="planeaciones">
                <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-2xl border bg-card p-4 text-center shadow-sm">
                        <p class="text-2xl font-bold">{{ contar(planeaciones, 'borrador') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
                            <FilePen class="size-3.5" /> Borradores
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-center shadow-sm dark:border-blue-900 dark:bg-blue-950/20">
                        <p class="text-2xl font-bold text-blue-600">{{ contar(planeaciones, 'enviada') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-blue-600">
                            <Send class="size-3.5" /> En revisión
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center shadow-sm dark:border-emerald-900 dark:bg-emerald-950/20">
                        <p class="text-2xl font-bold text-emerald-600">{{ contar(planeaciones, 'aprobada') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-emerald-600">
                            <CheckCircle2 class="size-3.5" /> Aprobadas
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-red-200 bg-red-50 p-4 text-center shadow-sm dark:border-red-900 dark:bg-red-950/20">
                        <p class="text-2xl font-bold text-red-600">{{ contar(planeaciones, 'rechazada') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-red-600">
                            <XCircle class="size-3.5" /> Rechazadas
                        </p>
                    </div>
                </div>

                <div class="mb-4 flex flex-wrap gap-1 border-b">
                    <button v-for="tab in tabs" :key="tab.value" type="button"
                        class="border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                        :class="tabActiva === tab.value ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="tabActiva = tab.value">
                        {{ tab.label }} ({{ contar(planeaciones, tab.value) }})
                    </button>
                </div>

                <div v-if="filtradas(planeaciones).length" class="space-y-3">
                    <div v-for="p in filtradas(planeaciones)" :key="p.id"
                        class="rounded-2xl border bg-card p-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <Link :href="PlaneacionController.show(p.id)" class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold">Semana {{ p.semana }} / {{ p.anio }}</p>
                                    <Badge :class="estadoBadgeClass[p.estado]"
                                        class="text-[10px] font-medium uppercase">
                                        {{ estadoLabel[p.estado] }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ p.proyecto?.nombre ?? '—' }} · {{ p.planta?.nombre ?? '—' }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ p.fechaInicio }} - {{ p.fechaFin }}</p>
                            </Link>

                            <div class="flex shrink-0 items-center gap-2">
                                <Button v-if="p.estado === 'borrador'" size="sm" @click="enviar(p.id)">
                                    <Send class="mr-1.5 size-3.5" />
                                    Enviar a revisión
                                </Button>
                                <Button v-if="p.estado === 'borrador' && props.puedeEliminar" size="sm"
                                    variant="outline" class="text-destructive hover:bg-destructive/10"
                                    @click="eliminar(p.id)">
                                    Eliminar
                                </Button>
                                <Link v-else-if="p.estado === 'borrador'" :href="PlaneacionController.show(p.id)">
                                    <Button size="sm" variant="outline">Editar</Button>
                                </Link>
                            </div>
                        </div>

                        <p v-if="p.estado === 'rechazada' && p.comentariosAprobacion"
                            class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/20 dark:text-red-400">
                            Motivo del rechazo: {{ p.comentariosAprobacion }}
                        </p>
                        <p v-else-if="p.estado === 'enviada'" class="mt-3 text-xs text-muted-foreground">
                            Enviada el {{ p.fechaEnvio }} · esperando revisión
                        </p>
                        <p v-else-if="p.estado === 'aprobada'" class="mt-3 text-xs text-muted-foreground">
                            Aprobada el {{ p.fechaAprobacion }} por {{ p.aprobador }}
                            <span v-if="p.reportadaNomina"> · ya reportada a nómina</span>
                        </p>
                    </div>
                </div>

                <div v-else
                    class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card/50 py-16 text-center shadow-sm">
                    <ClipboardList class="size-10 text-muted-foreground" />
                    <p class="text-lg font-semibold">No hay planeaciones</p>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        {{ tabActiva === 'todas' ? 'Aún no has creado ninguna planeación' : `No tienes planeaciones
                        ${estadoLabel[tabActiva as EstadoPlaneacion]?.toLowerCase() ?? ''}` }}
                    </p>
                    <Link v-if="props.puedeCrear && tabActiva === 'todas'" :href="PlaneacionController.create()">
                        <Button class="mt-2">Crear Primera Planeación</Button>
                    </Link>
                </div>
            </div>
        </Deferred>
    </PageLayout>
</template>
