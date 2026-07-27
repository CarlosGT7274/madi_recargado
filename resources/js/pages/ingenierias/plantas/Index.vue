<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
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

type PlantaResumen = {
    id: number;
    folio: string;
    nombre: string;
    direccion: string | null;
    activa: boolean;
    creada: string | null;
};

defineProps<{
    plantas: PlantaResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Plantas', href: PlantaController.index() },
        ],
    },
});

const dialogOpen = ref(false);
</script>

<template>
    <Head title="Plantas" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Heading
                title="Plantas"
                description="Plantas registradas en Ingenierías"
            />

            <Dialog v-model:open="dialogOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Nueva planta
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="PlantaController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        @success="dialogOpen = false"
                        v-slot="{ errors, processing }"
                        class="space-y-4"
                    >
                        <DialogHeader>
                            <DialogTitle>Nueva planta</DialogTitle>
                            <DialogDescription>
                                Registra una nueva planta para el módulo de Ingenierías.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="folio">Folio</Label>
                            <Input id="folio" name="folio" placeholder="PLT-0001" />
                            <InputError :message="errors.folio" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="nombre">Nombre</Label>
                            <Input id="nombre" name="nombre" placeholder="Nombre de la planta" />
                            <InputError :message="errors.nombre" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="direccion">Dirección</Label>
                            <Input id="direccion" name="direccion" placeholder="Dirección (opcional)" />
                            <InputError :message="errors.direccion" />
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
                class="grid grid-cols-[110px_1fr_1fr_100px_120px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground"
            >
                <span>Folio</span>
                <span>Nombre</span>
                <span>Dirección</span>
                <span>Estado</span>
                <span class="text-right">Creada</span>
            </div>

            <Link
                v-for="planta in plantas"
                :key="planta.id"
                :href="PlantaController.show(planta.id)"
                class="grid grid-cols-[110px_1fr_1fr_100px_120px] items-center gap-2 border-t px-4 py-3 text-sm hover:bg-accent"
            >
                <span class="font-medium">{{ planta.folio }}</span>
                <span class="truncate">{{ planta.nombre }}</span>
                <span class="truncate text-muted-foreground">{{ planta.direccion ?? '—' }}</span>
                <span
                    :class="planta.activa ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'"
                >
                    {{ planta.activa ? 'Activa' : 'Inactiva' }}
                </span>
                <span class="text-right text-muted-foreground">{{ planta.creada ?? '—' }}</span>
            </Link>

            <p v-if="!plantas.length" class="border-t px-4 py-8 text-center text-sm text-muted-foreground">
                Aún no hay plantas registradas.
            </p>
        </div>
    </div>
</template>
