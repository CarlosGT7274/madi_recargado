<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';

export default {
    layout: () => ({
        breadcrumbs: [
            { title: 'Plantas', href: PlantaController.index() },
            { title: (usePage().props.planta as { nombre: string })?.nombre ?? '', href: PlantaController.show((usePage().props.planta as { id: number })?.id) },
            { title: 'Levantamientos', href: '' },
        ],
    }),
};
</script>

<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from '@lucide/vue';
import { ref } from 'vue';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
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

import PageLayout from '@/components/PageLayout.vue';

type PlantaResumen = {
    id: number;
    nombre: string;
    folio: string;
};

type LevantamientoResumen = {
    id: number;
    folio: string;
    nombre: string;
    cliente: string;
    prioridad: string;
    estatus_admin: string;
    creado: string | null;
};

const props = defineProps<{
    planta: PlantaResumen;
    levantamientos: LevantamientoResumen[];
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
    revision_residente: 'Revisión',
    correcciones: 'Correcciones',
    lista_enviar: 'Lista enviar',
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
</script>

<template>
    <Head :title="`Levantamientos — ${planta.nombre}`" />

    <PageLayout
        :title="`Levantamientos — ${planta.nombre}`"
        description="Levantamientos registrados para esta planta"
        endpoint="ingenierias.levantamientos"
        with-create
        @create="dialogOpen = true"
    >
        <template #breadcrumbs>
            <Link
                :href="PlantaController.show(planta.id)"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <Form
                    v-bind="LevantamientoController.store.form(planta.id)"
                    reset-on-success
                    :options="{ preserveScroll: true }"
                    @success="dialogOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>Nuevo levantamiento</DialogTitle>
                        <DialogDescription>
                            Registra un nuevo levantamiento para la planta {{ planta.nombre }}.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="folio">Folio</Label>
                        <Input id="folio" name="folio" placeholder="LEV-0001" />
                        <InputError :message="errors.folio" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nombre">Nombre</Label>
                        <Input id="nombre" name="nombre" placeholder="Nombre del levantamiento" />
                        <InputError :message="errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="cliente">Cliente</Label>
                        <Input id="cliente" name="cliente" placeholder="Nombre del cliente" />
                        <InputError :message="errors.cliente" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="obra">Obra</Label>
                            <Input id="obra" name="obra" placeholder="Obra (opcional)" />
                            <InputError :message="errors.obra" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="solicitante">Solicitante</Label>
                            <Input id="solicitante" name="solicitante" placeholder="Solicitante (opcional)" />
                            <InputError :message="errors.solicitante" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="prioridad">Prioridad</Label>
                        <Select name="prioridad" default-value="normal">
                            <SelectTrigger id="prioridad">
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
                        <Button type="submit" :disabled="processing">Guardar</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="overflow-hidden rounded-xl border">
            <div
                class="grid grid-cols-[100px_1fr_1fr_110px_100px_100px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground"
            >
                <span>Folio</span>
                <span>Nombre</span>
                <span>Cliente</span>
                <span>Prioridad</span>
                <span>Estatus</span>
                <span class="text-right">Creado</span>
            </div>

            <Link
                v-for="lev in levantamientos"
                :key="lev.id"
                :href="LevantamientoController.show({ planta: planta.id, levantamiento: lev.id })"
                class="grid grid-cols-[100px_1fr_1fr_110px_100px_100px] items-center gap-2 border-t px-4 py-3 text-sm hover:bg-accent"
            >
                <span class="font-medium">{{ lev.folio }}</span>
                <span class="truncate">{{ lev.nombre }}</span>
                <span class="truncate text-muted-foreground">{{ lev.cliente }}</span>
                <span>
                    <Badge :variant="prioridadVariant(lev.prioridad)" class="text-xs">
                        {{ prioridadLabel[lev.prioridad] ?? lev.prioridad }}
                    </Badge>
                </span>
                <span class="text-xs text-muted-foreground">
                    {{ estatusLabel[lev.estatus_admin] ?? lev.estatus_admin }}
                </span>
                <span class="text-right text-muted-foreground">{{ lev.creado ?? '—' }}</span>
            </Link>

            <p v-if="!levantamientos.length" class="border-t px-4 py-8 text-center text-sm text-muted-foreground">
                Aún no hay levantamientos registrados para esta planta.
            </p>
        </div>
    </PageLayout>
</template>
