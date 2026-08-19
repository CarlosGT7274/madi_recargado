<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, FilePen, Plus, Send, XCircle } from '@lucide/vue';
import { ref } from 'vue';
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

const props = defineProps<{
    puedeCrear: boolean;
    puedeEliminar: boolean;
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mis Planeaciones', href: PlaneacionController.index() }],
    },
});

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
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-center shadow-sm dark:border-blue-900 dark:bg-blue-950/20">
                        <p class="text-2xl font-bold text-blue-600">{{ contar(planeaciones, 'enviada') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-blue-600">
                            <Send class="size-3.5" /> En revisión
                        </p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center shadow-sm dark:border-emerald-900 dark:bg-emerald-950/20">
                        <p class="text-2xl font-bold text-emerald-600">{{ contar(planeaciones, 'aprobada') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-emerald-600">
                            <CheckCircle2 class="size-3.5" /> Aprobadas
                        </p>
                    </div>
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-center shadow-sm dark:border-red-900 dark:bg-red-950/20">
                        <p class="text-2xl font-bold text-red-600">{{ contar(planeaciones, 'rechazada') }}</p>
                        <p class="flex items-center justify-center gap-1 text-xs text-red-600">
                            <XCircle class="size-3.5" /> Rechazadas
                        </p>
                    </div>
                </div>

                <div class="mb-4 flex flex-wrap gap-1 border-b">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                        :class="tabActiva === tab.value ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="tabActiva = tab.value"
                    >
                        {{ tab.label }} ({{ contar(planeaciones, tab.value) }})
                    </button>
                </div>

                <div v-if="filtradas(planeaciones).length" class="space-y-3">
                    <div v-for="p in filtradas(planeaciones)" :key="p.id" class="rounded-2xl border bg-card p-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <Link :href="PlaneacionController.show(p.id)" class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold">Semana {{ p.semana }} / {{ p.anio }}</p>
                                    <Badge :class="estadoBadgeClass[p.estado]" class="text-[10px] font-medium uppercase">
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
                                <Button
                                    v-if="p.estado === 'borrador' && props.puedeEliminar"
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:bg-destructive/10"
                                    @click="eliminar(p.id)"
                                >
                                    Eliminar
                                </Button>
                                <Link v-else-if="p.estado === 'borrador'" :href="PlaneacionController.show(p.id)">
                                    <Button size="sm" variant="outline">Editar</Button>
                                </Link>
                            </div>
                        </div>

                        <p v-if="p.estado === 'rechazada' && p.comentariosAprobacion" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/20 dark:text-red-400">
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

                <div v-else class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card/50 py-16 text-center shadow-sm">
                    <ClipboardList class="size-10 text-muted-foreground" />
                    <p class="text-lg font-semibold">No hay planeaciones</p>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        {{ tabActiva === 'todas' ? 'Aún no has creado ninguna planeación' : `No tienes planeaciones ${estadoLabel[tabActiva as EstadoPlaneacion]?.toLowerCase() ?? ''}` }}
                    </p>
                    <Link v-if="props.puedeCrear && tabActiva === 'todas'" :href="PlaneacionController.create()">
                        <Button class="mt-2">Crear Primera Planeación</Button>
                    </Link>
                </div>
            </div>
        </Deferred>
    </PageLayout>
</template>
