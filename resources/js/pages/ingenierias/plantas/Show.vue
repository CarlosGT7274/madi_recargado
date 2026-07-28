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
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
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

type LevantamientoResumen = {
    id: number;
    folio: string;
    nombre: string;
    cliente: string;
    area_trabajo: string | null;
    prioridad: string | null;
    estatus_admin: string | null;
    creado: string | null;
};

const props = defineProps<{
    planta: PlantaDetalle;
    levantamientos: LevantamientoResumen[];
}>();

const dialogOpen = ref(false);
const levantamientoDialogOpen = ref(false);

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

        <!-- Levantamientos de la planta -->
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Heading
                    title="Levantamientos"
                    description="Levantamientos registrados en esta planta"
                />

                <Dialog v-model:open="levantamientoDialogOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="mr-2 size-4" />
                            Nuevo levantamiento
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-h-[85vh] overflow-y-auto">
                        <Form
                            v-bind="LevantamientoController.store.form(planta.id)"
                            reset-on-success
                            :options="{ preserveScroll: true }"
                            @success="levantamientoDialogOpen = false"
                            v-slot="{ errors, processing }"
                            class="space-y-4"
                        >
                            <DialogHeader>
                                <DialogTitle>Nuevo levantamiento</DialogTitle>
                                <DialogDescription>
                                    Registra un levantamiento dentro de {{ planta.nombre }}.
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
                                <Input id="cliente" name="cliente" placeholder="Cliente" />
                                <InputError :message="errors.cliente" />
                            </div>

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

                            <div class="grid gap-2">
                                <Label for="area_trabajo">Área de trabajo</Label>
                                <Input id="area_trabajo" name="area_trabajo" placeholder="Área (opcional)" />
                                <InputError :message="errors.area_trabajo" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="prioridad">Prioridad</Label>
                                <select
                                    id="prioridad"
                                    name="prioridad"
                                    class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="normal">Normal</option>
                                    <option value="urgente">Urgente</option>
                                    <option value="grande_compleja">Grande / compleja</option>
                                </select>
                                <InputError :message="errors.prioridad" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="medio_solicitud">Medio de solicitud</Label>
                                <select
                                    id="medio_solicitud"
                                    name="medio_solicitud"
                                    class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="">— Sin especificar —</option>
                                    <option value="portal">Portal</option>
                                    <option value="correo">Correo</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="telefono">Teléfono</option>
                                </select>
                                <InputError :message="errors.medio_solicitud" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="notas_admin">Notas</Label>
                                <textarea
                                    id="notas_admin"
                                    name="notas_admin"
                                    rows="3"
                                    placeholder="Notas de prueba (opcional)"
                                    class="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm shadow-xs"
                                ></textarea>
                                <InputError :message="errors.notas_admin" />
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
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div
                    class="grid grid-cols-[110px_1fr_1fr_120px_120px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground"
                >
                    <span>Folio</span>
                    <span>Nombre</span>
                    <span>Cliente</span>
                    <span>Prioridad</span>
                    <span class="text-right">Creado</span>
                </div>

                <Link
                    v-for="levantamiento in levantamientos"
                    :key="levantamiento.id"
                    :href="LevantamientoController.show({ planta: planta.id, levantamiento: levantamiento.id })"
                    class="grid grid-cols-[110px_1fr_1fr_120px_120px] items-center gap-2 border-t px-4 py-3 text-sm hover:bg-accent"
                >
                    <span class="font-medium">{{ levantamiento.folio }}</span>
                    <span class="truncate">{{ levantamiento.nombre }}</span>
                    <span class="truncate text-muted-foreground">{{ levantamiento.cliente }}</span>
                    <span class="text-muted-foreground capitalize">{{ levantamiento.prioridad ?? '—' }}</span>
                    <span class="text-right text-muted-foreground">{{ levantamiento.creado ?? '—' }}</span>
                </Link>

                <p
                    v-if="!levantamientos.length"
                    class="border-t px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    Aún no hay levantamientos en esta planta.
                </p>
            </div>
        </div>
    </div>
</template>
