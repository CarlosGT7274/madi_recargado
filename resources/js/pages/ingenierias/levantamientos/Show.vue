<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';

export default {
    layout: () => {
        const props = usePage().props as {
            planta?: { id: number; nombre: string };
            levantamiento?: { nombre: string };
        };

        return {
            breadcrumbs: [
                { title: 'Plantas', href: PlantaController.index() },
                { title: props.planta?.nombre ?? '', href: PlantaController.show(props.planta?.id ?? 0) },
                { title: props.levantamiento?.nombre ?? '', href: '' },
            ],
        };
    },
};
</script>

<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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

type PlantaResumen = {
    id: number;
    nombre: string;
    folio: string;
};

type LevantamientoDetalle = {
    id: number;
    planta_id: number;
    folio: string;
    nombre: string;
    cliente: string;
    obra: string | null;
    solicitante: string | null;
    area_trabajo: string | null;
    prioridad: string | null;
    medio_solicitud: string | null;
    estatus_admin: string | null;
    notas_admin: string | null;
    creado: string | null;
    modificado: string | null;
};

const props = defineProps<{
    planta: PlantaResumen;
    levantamiento: LevantamientoDetalle;
}>();

const dialogOpen = ref(false);

function eliminar() {
    if (!confirm(`¿Eliminar el levantamiento "${props.levantamiento.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }

    router.delete(
        LevantamientoController.destroy({ planta: props.planta.id, levantamiento: props.levantamiento.id }).url,
    );
}
</script>

<template>
    <Head :title="`Levantamiento: ${levantamiento.nombre}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Heading
                :title="levantamiento.nombre"
                :description="`Folio ${levantamiento.folio} · ${planta.nombre}`"
            />
            <div class="flex items-center gap-2">
                <Dialog v-model:open="dialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">Editar</Button>
                    </DialogTrigger>
                    <DialogContent class="max-h-[85vh] overflow-y-auto">
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

                            <div class="grid gap-2">
                                <Label for="edit-area">Área de trabajo</Label>
                                <Input
                                    id="edit-area"
                                    name="area_trabajo"
                                    :default-value="levantamiento.area_trabajo ?? ''"
                                />
                                <InputError :message="errors.area_trabajo" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-prioridad">Prioridad</Label>
                                <select
                                    id="edit-prioridad"
                                    name="prioridad"
                                    :value="levantamiento.prioridad ?? 'normal'"
                                    class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="normal">Normal</option>
                                    <option value="urgente">Urgente</option>
                                    <option value="grande_compleja">Grande / compleja</option>
                                </select>
                                <InputError :message="errors.prioridad" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-notas">Notas</Label>
                                <textarea
                                    id="edit-notas"
                                    name="notas_admin"
                                    rows="3"
                                    :value="levantamiento.notas_admin ?? ''"
                                    class="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm shadow-xs"
                                ></textarea>
                                <InputError :message="errors.notas_admin" />
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
                <p class="text-xs font-medium text-muted-foreground">Área de trabajo</p>
                <p class="text-sm">{{ levantamiento.area_trabajo ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Prioridad</p>
                <p class="text-sm capitalize">{{ levantamiento.prioridad ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Medio de solicitud</p>
                <p class="text-sm capitalize">{{ levantamiento.medio_solicitud ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Estatus</p>
                <p class="text-sm">{{ levantamiento.estatus_admin ?? '—' }}</p>
            </div>

            <div class="space-y-1 md:col-span-2">
                <p class="text-xs font-medium text-muted-foreground">Notas</p>
                <p class="text-sm whitespace-pre-line">{{ levantamiento.notas_admin ?? '—' }}</p>
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
