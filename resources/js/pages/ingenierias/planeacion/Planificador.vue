<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, ChevronRight, Clock, Send, XCircle } from '@lucide/vue';
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
}

const props = defineProps<{
    puedeAprobar: boolean;
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Programación', href: PlaneacionController.index() }],
    },
});

function parseDdMmYyyy(fecha: string): Date {
    const [dia, mes, anio] = fecha.split('/').map(Number);
    return new Date(anio, mes - 1, dia);
}

function toIso(fecha: Date): string {
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
}

const hoy = new Date();
const mesActual = ref(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
const diaSeleccionado = ref<string | null>(null);

const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

function irAMes(indice: number): void {
    mesActual.value = new Date(mesActual.value.getFullYear(), indice, 1);
    diaSeleccionado.value = null;
}

function cambiarMes(delta: number): void {
    mesActual.value = new Date(mesActual.value.getFullYear(), mesActual.value.getMonth() + delta, 1);
    diaSeleccionado.value = null;
}

function cambiarAnio(delta: number): void {
    mesActual.value = new Date(mesActual.value.getFullYear() + delta, mesActual.value.getMonth(), 1);
    diaSeleccionado.value = null;
}

const diasDelMes = computed(() => {
    const year = mesActual.value.getFullYear();
    const month = mesActual.value.getMonth();
    const primerDia = new Date(year, month, 1);
    const inicioOffset = (primerDia.getDay() + 6) % 7;
    const totalDias = new Date(year, month + 1, 0).getDate();

    const celdas: { fecha: Date | null; iso: string | null }[] = [];
    for (let i = 0; i < inicioOffset; i++) celdas.push({ fecha: null, iso: null });
    for (let d = 1; d <= totalDias; d++) {
        const fecha = new Date(year, month, d);
        celdas.push({ fecha, iso: toIso(fecha) });
    }
    return celdas;
});

function estadoDelDia(iso: string): EstadoPlaneacion | null {
    const lista = props.planeaciones ?? [];
    const fecha = parseDdMmYyyy(`${iso.split('-')[2]}/${iso.split('-')[1]}/${iso.split('-')[0]}`);

    const encontrada = lista.find((p) => {
        const inicio = parseDdMmYyyy(p.fechaInicio);
        const fin = parseDdMmYyyy(p.fechaFin);
        return fecha >= inicio && fecha <= fin;
    });

    return encontrada?.estado ?? null;
}

const puntoClase: Record<EstadoPlaneacion, string> = {
    borrador: 'bg-muted-foreground',
    enviada: 'bg-blue-500',
    aprobada: 'bg-emerald-500',
    rechazada: 'bg-red-500',
};

const planeacionesDelDia = computed<PlaneacionResumen[]>(() => {
    if (!diaSeleccionado.value) return [];
    const fecha = new Date(diaSeleccionado.value);

    return (props.planeaciones ?? []).filter((p) => {
        const inicio = parseDdMmYyyy(p.fechaInicio);
        const fin = parseDdMmYyyy(p.fechaFin);
        return fecha >= inicio && fecha <= fin;
    });
});

const pendientesDeRevision = computed(
    () => (props.planeaciones ?? []).filter((p) => p.estado === 'enviada'),
);

const listaVisible = computed<PlaneacionResumen[]>(
    () => (diaSeleccionado.value ? planeacionesDelDia.value : pendientesDeRevision.value),
);

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

const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

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
</script>

<template>
    <Head title="Programación" />

    <PageLayout title="Programación" description="Visión general de las planeaciones por periodo">
        <Deferred data="planeaciones">
            <template #fallback>
                <div class="h-96 animate-pulse rounded-2xl border bg-card/50" />
            </template>

            <div v-if="planeaciones" class="grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarAnio(-1)">
                                <ChevronLeft class="size-4" />
                            </button>
                            <p class="w-14 text-center text-sm font-medium">{{ mesActual.getFullYear() }}</p>
                            <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarAnio(1)">
                                <ChevronRight class="size-4" />
                            </button>
                        </div>

                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarMes(-1)">
                                <ChevronLeft class="size-4" />
                            </button>
                            <p class="w-28 text-center text-sm font-semibold">{{ nombresMeses[mesActual.getMonth()] }}</p>
                            <button type="button" class="rounded-md p-1 hover:bg-accent" @click="cambiarMes(1)">
                                <ChevronRight class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-wrap gap-1">
                        <button
                            v-for="(nombre, indice) in nombresMeses"
                            :key="nombre"
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-medium transition-colors"
                            :class="mesActual.getMonth() === indice ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                            @click="irAMes(indice)"
                        >
                            {{ nombre.slice(0, 3) }}
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground">
                        <span v-for="(d, i) in diasSemana" :key="`${d}-${i}`">{{ d }}</span>
                    </div>

                    <div class="mt-1 grid grid-cols-7 gap-1">
                        <button
                            v-for="(celda, idx) in diasDelMes"
                            :key="idx"
                            type="button"
                            :disabled="!celda.fecha"
                            class="relative flex aspect-square flex-col items-center justify-center gap-0.5 rounded-md text-sm transition-colors disabled:cursor-default"
                            :class="[
                                !celda.fecha ? 'invisible' : 'hover:bg-accent',
                                diaSeleccionado === celda.iso ? 'bg-primary text-primary-foreground hover:bg-primary' : '',
                            ]"
                            @click="diaSeleccionado = diaSeleccionado === celda.iso ? null : celda.iso"
                        >
                            {{ celda.fecha?.getDate() }}
                            <span
                                v-if="celda.iso && estadoDelDia(celda.iso)"
                                class="size-1.5 rounded-full"
                                :class="puntoClase[estadoDelDia(celda.iso) as EstadoPlaneacion]"
                            />
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Clock class="size-4" />
                        {{ diaSeleccionado ? 'Planeaciones de este día' : 'Pendientes de revisión' }}
                    </div>

                    <div v-if="listaVisible.length" class="space-y-3">
                        <div v-for="p in listaVisible" :key="p.id" class="rounded-xl border p-3">
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

                            <div v-if="props.puedeAprobar && p.estado === 'enviada'" class="mt-3 flex gap-2">
                                <Button size="sm" class="flex-1 bg-emerald-600 text-white hover:bg-emerald-700" @click="aprobar(p.id)">
                                    <CheckCircle2 class="mr-1.5 size-3.5" />
                                    Aprobar
                                </Button>
                                <Button size="sm" variant="outline" class="flex-1 border-red-300 text-red-600 hover:bg-red-50" @click="rechazar(p.id)">
                                    <XCircle class="mr-1.5 size-3.5" />
                                    Rechazar
                                </Button>
                            </div>
                            <Button
                                v-else-if="p.estado === 'aprobada' && !p.reportadaNomina"
                                size="sm"
                                variant="outline"
                                class="mt-3 w-full"
                                @click="reportarNomina(p.id)"
                            >
                                <Send class="mr-1.5 size-3.5" />
                                Reportar a nómina
                            </Button>
                        </div>
                    </div>

                    <p v-else class="py-8 text-center text-sm text-muted-foreground">
                        {{ diaSeleccionado ? 'Sin planeaciones ese día' : 'Nada pendiente de revisión' }}
                    </p>
                </div>
            </div>
        </Deferred>
    </PageLayout>
</template>
