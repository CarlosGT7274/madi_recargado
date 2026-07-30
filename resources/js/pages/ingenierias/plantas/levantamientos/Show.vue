<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';

export default {
    layout: () => ({
        breadcrumbs: [
            { title: 'Plantas', href: PlantaController.index() },
            {
                title: (usePage().props.planta as { nombre: string })?.nombre ?? '',
                href: PlantaController.show((usePage().props.planta as { id: number })?.id),
            },
            {
                title: (usePage().props.levantamiento as { folio: string })?.folio ?? '',
                href: '',
            },
        ],
    }),
};
</script>

<script setup lang="ts">
import { Deferred, Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeft,
    Building2,
    Calendar,
    ClipboardList,
    FileSpreadsheet,
    FileText,
    Plus,
    User,
} from '@lucide/vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PageLayout from '@/components/PageLayout.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type PlantaResumen = {
    id: number;
    nombre: string;
};

type LevantamientoDetalle = {
    id: number;
    planta_id: number;
    folio: string;
    nombre: string;
    cliente: string;
    obra: string | null;
    solicitante: string | null;
    prioridad: string;
    estatus_admin: string;
    creado: string | null;
    modificado: string | null;
};

type CotizacionResumen = {
    id: number;
    folio: string;
    fecha: string | null;
    cliente: string | null;
    vendedor: string | null;
    total: number | null;
    estado: string;
};

const props = defineProps<{
    planta: PlantaResumen;
    levantamiento: LevantamientoDetalle;
    // Deferred: llega undefined en el render inicial, Inertia la pide sola
    // en una segunda petición y actualiza esta prop cuando responde.
    cotizaciones?: CotizacionResumen[];
}>();

const editDialogOpen = ref(false);
const createCotizacionDialogOpen = ref(false);

function eliminarLevantamiento() {
    if (!confirm(`¿Eliminar el levantamiento "${props.levantamiento.folio}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(
        LevantamientoController.destroy({ planta: props.planta.id, levantamiento: props.levantamiento.id }).url,
        { onSuccess: () => router.visit(PlantaController.show(props.planta.id).url) },
    );
}

const prioridadLabel: Record<string, string> = {
    urgente: 'Urgente',
    normal: 'Normal',
    grande_compleja: 'Grande / Compleja',
};

const estatusLabel: Record<string, string> = {
    recibida: 'Recibida',
    levantamiento_proceso: 'En proceso',
    levantamiento_listo: 'Listo',
    cotizando: 'Cotizando',
    revision_residente: 'Revisión',
    correcciones: 'Correcciones',
    lista_enviar: 'Lista enviar',
    enviada: 'Enviada',
    ganada: 'Ganada',
    perdida: 'Perdida',
    cancelada: 'Cancelada',
};

const estatusGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    recibida: 'pendiente',
    levantamiento_proceso: 'pendiente',
    levantamiento_listo: 'pendiente',
    cotizando: 'pendiente',
    revision_residente: 'pendiente',
    correcciones: 'pendiente',
    lista_enviar: 'pendiente',
    enviada: 'pendiente',
    ganada: 'aprobado',
    perdida: 'negativo',
    cancelada: 'negativo',
};

function estatusBadgeClass(estatus: string) {
    const grupo = estatusGrupo[estatus] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function prioridadVariant(prioridad: string) {
    if (prioridad === 'urgente') return 'destructive';
    if (prioridad === 'grande_compleja') return 'outline';
    return 'secondary';
}

const estadoCotizacionLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const estadoCotizacionGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    borrador: 'pendiente',
    enviada: 'pendiente',
    aprobada: 'aprobado',
    rechazada: 'negativo',
};

function estadoCotizacionBadgeClass(estado: string) {
    const grupo = estadoCotizacionGrupo[estado] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function formatoMoneda(valor: number | null) {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>
    <Head :title="`Levantamiento ${levantamiento.folio}`" />

    <PageLayout
        :title="levantamiento.folio"
        :description="levantamiento.nombre"
        endpoint="ingenierias.plantas.levantamientos"
        with-edit
        with-delete
        @edit="editDialogOpen = true"
        @delete="eliminarLevantamiento"
    >
        <template #breadcrumbs>
            <Link
                :href="PlantaController.show(planta.id)"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <!-- Dialog: editar levantamiento -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <Form
                    v-bind="LevantamientoController.update.form({ planta: planta.id, levantamiento: levantamiento.id })"
                    :options="{ preserveScroll: true }"
                    @success="editDialogOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>Editar levantamiento</DialogTitle>
                        <DialogDescription>Actualiza los datos de este levantamiento.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit-folio">Folio</Label>
                        <Input id="edit-folio" name="folio" :default-value="levantamiento.folio" />
                        <InputError :message="errors.folio" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-nombre">Nombre</Label>
                        <Input id="edit-nombre" name="nombre" :default-value="levantamiento.nombre" />
                        <InputError :message="errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-cliente">Cliente</Label>
                        <Input id="edit-cliente" name="cliente" :default-value="levantamiento.cliente" />
                        <InputError :message="errors.cliente" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-obra">Obra</Label>
                            <Input id="edit-obra" name="obra" :default-value="levantamiento.obra ?? ''" />
                            <InputError :message="errors.obra" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-solicitante">Solicitante</Label>
                            <Input id="edit-solicitante" name="solicitante" :default-value="levantamiento.solicitante ?? ''" />
                            <InputError :message="errors.solicitante" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-prioridad">Prioridad</Label>
                        <Select name="prioridad" :default-value="levantamiento.prioridad">
                            <SelectTrigger id="edit-prioridad">
                                <SelectValue placeholder="Seleccionar prioridad" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="normal">Normal</SelectItem>
                                <SelectItem value="urgente">Urgente</SelectItem>
                                <SelectItem value="grande_compleja">Grande / Compleja</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.prioridad" />
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

        <!-- Dialog: crear cotización -->
        <Dialog v-model:open="createCotizacionDialogOpen">
            <DialogContent>
                <Form
                    v-bind="CotizacionController.store.form({ planta: planta.id, levantamiento: levantamiento.id })"
                    reset-on-success
                    :options="{ preserveScroll: true }"
                    @success="createCotizacionDialogOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>Nueva cotización</DialogTitle>
                        <DialogDescription>
                            Registra una nueva cotización para el levantamiento {{ levantamiento.folio }}.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="cot-folio">Folio</Label>
                            <Input id="cot-folio" name="folio" placeholder="COT-0001" />
                            <InputError :message="errors.folio" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cot-fecha">Fecha</Label>
                            <Input id="cot-fecha" name="fecha" type="date" />
                            <InputError :message="errors.fecha" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="cot-cliente">Cliente</Label>
                        <Input id="cot-cliente" name="cliente" :default-value="levantamiento.cliente" />
                        <InputError :message="errors.cliente" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="cot-vendedor">Vendedor</Label>
                            <Input id="cot-vendedor" name="vendedor" placeholder="Vendedor (opcional)" />
                            <InputError :message="errors.vendedor" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cot-proveedor">Proveedor</Label>
                            <Input id="cot-proveedor" name="proveedor" placeholder="Proveedor (opcional)" />
                            <InputError :message="errors.proveedor" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="cot-subtotal">Subtotal</Label>
                            <Input id="cot-subtotal" name="subtotal" type="number" step="0.01" placeholder="0.00" />
                            <InputError :message="errors.subtotal" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cot-iva">IVA</Label>
                            <Input id="cot-iva" name="iva" type="number" step="0.01" placeholder="0.00" />
                            <InputError :message="errors.iva" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cot-total">Total</Label>
                            <Input id="cot-total" name="total" type="number" step="0.01" placeholder="0.00" />
                            <InputError :message="errors.total" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="cot-moneda">Moneda</Label>
                        <Select name="moneda" default-value="PESOS MXN">
                            <SelectTrigger id="cot-moneda">
                                <SelectValue placeholder="Seleccionar moneda" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="PESOS MXN">Pesos MXN</SelectItem>
                                <SelectItem value="USD">USD</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.moneda" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="cot-notas">Notas</Label>
                        <Input id="cot-notas" name="notas" placeholder="Notas (opcional)" />
                        <InputError :message="errors.notas" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="space-y-6">
            <!-- Header -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b bg-muted/30 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <ClipboardList class="size-6" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-lg font-semibold leading-tight">{{ levantamiento.folio }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium uppercase"
                                    :class="estatusBadgeClass(levantamiento.estatus_admin)"
                                >
                                    {{ estatusLabel[levantamiento.estatus_admin] ?? levantamiento.estatus_admin }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ levantamiento.nombre }}</p>
                        </div>
                    </div>

                    <Badge :variant="prioridadVariant(levantamiento.prioridad)">
                        {{ prioridadLabel[levantamiento.prioridad] ?? levantamiento.prioridad }}
                    </Badge>
                </div>
            </div>

            <!-- Detalle -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Building2 class="size-4" />
                        Datos generales
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Planta</dt>
                            <dd class="font-medium">{{ planta.nombre }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Cliente</dt>
                            <dd class="font-medium">{{ levantamiento.cliente }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Obra</dt>
                            <dd class="font-medium">{{ levantamiento.obra ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <User class="size-4" />
                        Solicitud
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Solicitante</dt>
                            <dd class="font-medium">{{ levantamiento.solicitante ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Prioridad</dt>
                            <dd class="font-medium">{{ prioridadLabel[levantamiento.prioridad] ?? levantamiento.prioridad }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Estatus</dt>
                            <dd class="font-medium">{{ estatusLabel[levantamiento.estatus_admin] ?? levantamiento.estatus_admin }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm md:col-span-2">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Calendar class="size-4" />
                        Fechas
                    </div>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Creado</dt>
                            <dd class="font-medium">{{ levantamiento.creado ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Última modificación</dt>
                            <dd class="font-medium">{{ levantamiento.modificado ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Cotizaciones -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <FileSpreadsheet class="size-4" />
                        Cotizaciones
                    </div>
                    <Button size="sm" @click="createCotizacionDialogOpen = true">
                        <Plus class="size-4" />
                        Crear cotización
                    </Button>
                </div>

                <Deferred data="cotizaciones">
                    <template #fallback>
                        <p class="py-6 text-center text-sm text-muted-foreground">Cargando cotizaciones…</p>
                    </template>

                    <div v-if="cotizaciones?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="cot in cotizaciones"
                            :key="cot.id"
                            :href="
                                CotizacionController.show({
                                    planta: planta.id,
                                    levantamiento: levantamiento.id,
                                    cotizacion: cot.id,
                                })
                            "
                            class="flex items-start gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-accent/50"
                        >
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileText class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-semibold">{{ cot.folio }}</p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                        :class="estadoCotizacionBadgeClass(cot.estado)"
                                    >
                                        {{ estadoCotizacionLabel[cot.estado] ?? cot.estado }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm text-muted-foreground">{{ cot.cliente ?? '—' }}</p>
                                <p class="mt-1 text-sm font-medium">{{ formatoMoneda(cot.total) }}</p>
                            </div>
                        </Link>
                    </div>

                    <p v-else class="py-6 text-center text-sm text-muted-foreground">
                        Aún no hay cotizaciones para este levantamiento.
                    </p>
                </Deferred>
            </div>
        </div>
    </PageLayout>
</template>
