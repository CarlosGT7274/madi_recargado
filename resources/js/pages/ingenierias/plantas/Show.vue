<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsPlanta, type PlantaRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
}

export default pageLayout(() => breadcrumbsPlanta(usePage<Props>().props.planta));
</script>

<script setup lang="ts">
import { Deferred, Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Building2,
    CircleCheck,
    Clock,
    FolderOpen,
    FolderPlus,
    Layers,
    Plus,
} from '@lucide/vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import PageLayout from '@/components/PageLayout.vue';
import PermissionButton from '@/components/PermissionButton.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';

type PlantaDetalle = {
    id: number;
    folio: string;
    nombre: string;
    direccion: string | null;
    descripcion: string | null;
    activa: boolean;
    creada: string | null;
    modificada: string | null;
};

type ProyectoResumen = {
    id: number;
    folio: string;
    nombre: string;
    tipo: string;
    estado: string;
    bloqueado: boolean;
    creado: string | null;
    creado_iso: string | null;
};

const props = defineProps<{
    planta: PlantaDetalle;
    proyectos?: ProyectoResumen[];
}>();

const { Accion } = usePermissions();

const editDialogOpen = ref(false);

function eliminarPlanta() {
    if (!confirm(`¿Eliminar la planta "${props.planta.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(PlantaController.destroy(props.planta.id).url);
}

const estatusLabel: Record<string, string> = {
    activo: 'Activo',
    terminado: 'Terminado',
    cancelado: 'Cancelado',
};

const estatusGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    activo: 'pendiente',
    terminado: 'aprobado',
    cancelado: 'negativo',
};

function estatusBadgeClass(estado: string) {
    const grupo = estatusGrupo[estado] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function tipoVariant(tipo: string) {
    if (tipo === 'grande') return 'default';
    return 'secondary';
}

function tipoLabel(tipo: string) {
    if (tipo === 'grande') return 'Proyecto estándar';
    if (tipo === 'chico') return 'Proyecto directo';
    return tipo;
}

const totales = computed(() => {
    const lista = props.proyectos ?? [];
    const terminados = lista.filter((p) => estatusGrupo[p.estado] === 'aprobado').length;
    const activos = lista.filter((p) => (estatusGrupo[p.estado] ?? 'pendiente') === 'pendiente').length;
    return { terminados, activos, total: lista.length };
});

// --- Filtro por tipo de flujo ---
type FiltroTipo = 'todos' | 'grande' | 'chico';
const filtroTipo = ref<FiltroTipo>('todos');

const filtros: { value: FiltroTipo; label: string }[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'grande', label: 'Proyecto estándar' },
    { value: 'chico', label: 'Proyecto directo' },
];

const proyectosFiltrados = computed(() => {
    const lista = props.proyectos ?? [];
    if (filtroTipo.value === 'todos') return lista;
    return lista.filter((p) => p.tipo === filtroTipo.value);
});

function contarPorTipo(tipo: FiltroTipo): number {
    const lista = props.proyectos ?? [];
    if (tipo === 'todos') return lista.length;
    return lista.filter((p) => p.tipo === tipo).length;
}
</script>

<template>

    <Head :title="`Planta: ${planta.nombre}`" />

    <PageLayout :title="planta.nombre" :description="`Folio ${planta.folio}`" endpoint="ingenierias.plantas" with-edit
        with-delete @edit="editDialogOpen = true" @delete="eliminarPlanta">
        <!-- Dialog: editar planta -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <Form v-bind="PlantaController.update.form(planta.id)" :options="{ preserveScroll: true }"
                    @success="editDialogOpen = false" v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Editar planta</DialogTitle>
                        <DialogDescription>Actualiza los datos de esta planta.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit-nombre">Nombre</Label>
                        <Input id="edit-nombre" name="nombre" :default-value="planta.nombre" />
                        <InputError :message="errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-direccion">Dirección</Label>
                        <Input id="edit-direccion" name="direccion" :default-value="planta.direccion ?? ''" />
                        <InputError :message="errors.direccion" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar cambios</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="space-y-6">
            <!-- Hero: identidad de la planta + entrada al módulo de proyectos -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-col gap-6 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Building2 class="size-7" />
                        </div>
                        <div>
                            <p class="text-xl font-semibold leading-tight">{{ planta.nombre }}</p>
                            <p class="text-sm text-muted-foreground">{{ planta.folio }}</p>
                            <p v-if="planta.direccion" class="mt-0.5 text-sm text-muted-foreground">
                                {{ planta.direccion }}
                            </p>
                        </div>
                    </div>

                    <PermissionButton endpoint="ingenierias.plantas.proyectos" :accion="Accion.CREATE"
                        :href="ProyectoController.create(planta.id)" size="lg">
                        <FolderPlus class="size-4" />
                        Nuevo Proyecto
                    </PermissionButton>
                </div>

                <!-- Totales: resumen simple, sin calendario -->
                <Deferred data="proyectos">
                    <template #fallback>
                        <div class="grid grid-cols-3 divide-x border-t py-5 text-center text-sm text-muted-foreground">
                            <div>Cargando…</div>
                            <div>Cargando…</div>
                            <div>Cargando…</div>
                        </div>
                    </template>

                    <div class="grid grid-cols-3 divide-x border-t">
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="flex items-center gap-1 text-2xl font-bold text-emerald-600">
                                <CircleCheck class="size-5" />
                                {{ totales.terminados }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Terminados</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="flex items-center gap-1 text-2xl font-bold text-amber-600">
                                <Clock class="size-5" />
                                {{ totales.activos }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Activos</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 py-4">
                            <span class="text-2xl font-bold">{{ totales.total }}</span>
                            <span class="text-xs uppercase tracking-wide text-muted-foreground">Total</span>
                        </div>
                    </div>
                </Deferred>
            </div>

            <!-- Filtro por tipo de flujo -->
            <Deferred data="proyectos">
                <template #fallback />

                <div v-if="(proyectos?.length ?? 0) > 0" class="flex flex-wrap gap-2">
                    <button v-for="filtro in filtros" :key="filtro.value" type="button"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors" :class="filtroTipo === filtro.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent'" @click="filtroTipo = filtro.value">
                        {{ filtro.label }} ({{ contarPorTipo(filtro.value) }})
                    </button>
                </div>
            </Deferred>

            <!-- Proyectos: grid de tarjetas, pantalla de inicio del módulo -->
            <Deferred data="proyectos">
                <template #fallback>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="n in 3" :key="n"
                            class="h-32 animate-pulse rounded-2xl border bg-card/50 shadow-sm" />
                    </div>
                </template>

                <div v-if="proyectosFiltrados.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="pry in proyectosFiltrados" :key="pry.id"
                        :href="ProyectoController.show([planta.id, pry.id])"
                        class="group flex flex-col gap-3 rounded-2xl border bg-card p-5 shadow-sm transition-colors hover:bg-accent/50">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                    <FolderOpen class="size-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ pry.folio }}</p>
                                    <p class="truncate text-sm text-muted-foreground">{{ pry.nombre ?? '—' }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium uppercase"
                                :class="estatusBadgeClass(pry.estado)">
                                {{ estatusLabel[pry.estado] ?? pry.estado }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <Badge :variant="tipoVariant(pry.tipo)" class="gap-1 text-[10px]">
                                <Layers class="size-3" />
                                {{ tipoLabel(pry.tipo) }}
                            </Badge>
                            <span>Creado: {{ pry.creado ?? '—' }}</span>
                        </div>
                    </Link>
                </div>

                <div v-else-if="!(proyectos?.length ?? 0)"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card/50 py-16 text-center shadow-sm">
                    <FolderOpen class="size-8 text-muted-foreground" />
                    <p class="text-sm font-medium">Aún no hay proyectos en esta planta</p>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        Crea el primer proyecto para empezar a trabajar sobre esta planta.
                    </p>
                    <PermissionButton endpoint="ingenierias.plantas.proyectos" :accion="Accion.CREATE"
                        :href="ProyectoController.create(planta.id)" class="mt-2">
                        <Plus class="mr-2 size-4" />
                        Nuevo Proyecto
                    </PermissionButton>
                </div>

                <p v-else
                    class="rounded-2xl border border-dashed bg-card/50 py-12 text-center text-sm text-muted-foreground">
                    No hay proyectos de tipo "{{filtros.find(f => f.value === filtroTipo)?.label}}".
                </p>
            </Deferred>
        </div>
    </PageLayout>
</template>
