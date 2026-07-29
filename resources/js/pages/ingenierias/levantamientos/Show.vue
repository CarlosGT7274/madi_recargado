<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';

export default {
    layout: () => ({
        breadcrumbs: [
            { title: 'Plantas', href: PlantaController.index() },
            {
                title: (usePage().props.planta as { nombre: string })?.nombre ?? '',
                href: PlantaController.show((usePage().props.planta as { id: number })?.id),
            },
            {
                title: 'Levantamientos',
                href: LevantamientoController.index((usePage().props.planta as { id: number })?.id),
            },
            { title: (usePage().props.levantamiento as { nombre: string })?.nombre ?? '', href: '' },
        ],
    }),
};
</script>

<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref } from 'vue';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import Heading from '@/components/Heading.vue';
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
    DialogTrigger,
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

const props = defineProps<{
    planta: PlantaResumen;
    levantamiento: LevantamientoDetalle;
}>();

const dialogOpen = ref(false);

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
    revision_residente: 'Revisión residente',
    correcciones: 'Correcciones',
    lista_enviar: 'Lista para enviar',
    enviada: 'Enviada',
    ganada: 'Ganada',
    perdida: 'Perdida',
    cancelada: 'Cancelada',
};

function prioridadVariant(prioridad: string) {
    if (prioridad === 'urgente') {
        return 'destructive';
    }

    if (prioridad === 'grande_compleja') {
        return 'outline';
    }

    return 'secondary';
}

function eliminar() {
    if (!confirm(`¿Eliminar el levantamiento "${props.levantamiento.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }

    router.delete(LevantamientoController.destroy({ planta: props.planta.id, levantamiento: props.levantamiento.id }).url);
}
</script>

<template>
    <Head :title="`Levantamiento: ${levantamiento.nombre}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Link
                    :href="LevantamientoController.index(planta.id)"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                </Link>
                <Heading
                    :title="levantamiento.nombre"
                    :description="`Folio ${levantamiento.folio}`"
                />
            </div>
            <div class="flex items-center gap-2">
                <Dialog v-model:open="dialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">Editar</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <Form
                            v-bind="LevantamientoController.update.form({ planta: planta.id, levantamiento: levantamiento.id })"
                            :options="{ preserveScroll: true }"
                            @success="dialogOpen = false"
                            v-slot="{ errors, processing }"
                            class="space-y-4"
                        >
                            <DialogHeader>
                                <DialogTitle>Editar levantamiento</DialogTitle>
                                <DialogDescription>
                                    Actualiza los datos de este levantamiento.
                                </DialogDescription>
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
                                    <Input
                                        id="edit-solicitante"
                                        name="solicitante"
                                        :default-value="levantamiento.solicitante ?? ''"
                                    />
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

                <Button variant="destructive" @click="eliminar">Eliminar</Button>
            </div>
        </div>

        <div class="grid gap-6 rounded-xl border p-6 md:grid-cols-2">
            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Folio</p>
                <p class="text-sm">{{ levantamiento.folio }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Cliente</p>
                <p class="text-sm">{{ levantamiento.cliente }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Obra</p>
                <p class="text-sm">{{ levantamiento.obra ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Solicitante</p>
                <p class="text-sm">{{ levantamiento.solicitante ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Prioridad</p>
                <Badge :variant="prioridadVariant(levantamiento.prioridad)" class="text-xs">
                    {{ prioridadLabel[levantamiento.prioridad] ?? levantamiento.prioridad }}
                </Badge>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Estatus</p>
                <p class="text-sm">{{ estatusLabel[levantamiento.estatus_admin] ?? levantamiento.estatus_admin }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Creado</p>
                <p class="text-sm">{{ levantamiento.creado ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Última modificación</p>
                <p class="text-sm">{{ levantamiento.modificado ?? '—' }}</p>
            </div>
        </div>
    </div>
</template>
