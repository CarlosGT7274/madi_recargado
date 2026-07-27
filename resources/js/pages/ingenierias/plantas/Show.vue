<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';

export default {
    layout: () => ({
        breadcrumbs: [
            { title: 'Plantas', href: PlantaController.index() },
            { title: (usePage().props.planta as { nombre: string })?.nombre ?? '', href: '' },
        ],
    }),
};
</script>

<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
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

const props = defineProps<{
    planta: PlantaDetalle;
}>();

const dialogOpen = ref(false);

function eliminar() {
    if (!confirm(`¿Eliminar la planta "${props.planta.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }

    router.delete(PlantaController.destroy(props.planta.id).url);
}
</script>

<template>
    <Head :title="`Planta: ${planta.nombre}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Heading
                :title="planta.nombre"
                :description="`Folio ${planta.folio}`"
            />
            <div class="flex items-center gap-2">
                <Dialog v-model:open="dialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">Editar</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <Form
                            v-bind="PlantaController.update.form(planta.id)"
                            :options="{ preserveScroll: true }"
                            @success="dialogOpen = false"
                            v-slot="{ errors, processing }"
                            class="space-y-4"
                        >
                            <DialogHeader>
                                <DialogTitle>Editar planta</DialogTitle>
                                <DialogDescription>
                                    Actualiza los datos de esta planta.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-2">
                                <Label for="edit-folio">Folio</Label>
                                <Input id="edit-folio" name="folio" :default-value="planta.folio" />
                                <InputError :message="errors.folio" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-nombre">Nombre</Label>
                                <Input id="edit-nombre" name="nombre" :default-value="planta.nombre" />
                                <InputError :message="errors.nombre" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-direccion">Dirección</Label>
                                <Input
                                    id="edit-direccion"
                                    name="direccion"
                                    :default-value="planta.direccion ?? ''"
                                />
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

                <Button variant="destructive" @click="eliminar">Eliminar</Button>
            </div>
        </div>

        <div class="grid gap-6 rounded-xl border p-6 md:grid-cols-2">
            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Folio</p>
                <p class="text-sm">{{ planta.folio }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Estado</p>
                <p class="text-sm">{{ planta.activa ? 'Activa' : 'Inactiva' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Dirección</p>
                <p class="text-sm">{{ planta.direccion ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Descripción</p>
                <p class="text-sm">{{ planta.descripcion ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Creada</p>
                <p class="text-sm">{{ planta.creada ?? '—' }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">Última modificación</p>
                <p class="text-sm">{{ planta.modificada ?? '—' }}</p>
            </div>
        </div>
    </div>
</template>
