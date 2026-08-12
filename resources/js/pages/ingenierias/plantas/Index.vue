<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Building2, CalendarClock, CircleAlert, ShoppingCart } from '@lucide/vue';
import { ref } from 'vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import InputError from '@/components/InputError.vue';
import PageLayout from '@/components/PageLayout.vue';
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

type EstatusResumen = { estatus: string; total: number };

type PlantaResumen = {
    id: number;
    folio: string;
    nombre: string;
    direccion: string | null;
    activa: boolean;
    proyectosCount: number;
    levantamientosCount: number;
    porEstatus: EstatusResumen[];
    urgentes: number;
    programados: number;
    cotizados: number;
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
</script>

<template>

    <Head title="Plantas" />

    <PageLayout title="Plantas" description="Gestiona las plantas y sus levantamientos" endpoint="ingenierias.plantas"
        with-create @create="dialogOpen = true">
        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <Form :action="PlantaController.store().url" :method="PlantaController.store().method" reset-on-success :options="{ preserveScroll: true }"
                    @success="dialogOpen = false" v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Nueva planta</DialogTitle>
                        <DialogDescription>
                            El folio se genera automáticamente al guardar.
                        </DialogDescription>
                    </DialogHeader>

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

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="planta in plantas" :key="planta.id" :href="PlantaController.show(planta.id)"
                class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-colors hover:bg-accent/50">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Building2 class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold leading-tight">{{ planta.nombre }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ planta.levantamientosCount }}
                                {{ planta.levantamientosCount === 1 ? 'levantamiento' : 'levantamientos' }}
                            </p>
                        </div>
                    </div>

                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                        :class="planta.activa ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">
                        {{ planta.activa ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>

                <div v-if="planta.porEstatus.length" class="flex flex-col gap-1.5">
                    <p class="text-xs font-medium text-muted-foreground">Por estatus:</p>
                    <div class="flex flex-wrap gap-1.5">
                        <Badge v-for="grupo in planta.porEstatus" :key="grupo.estatus" variant="secondary"
                            class="text-[11px] font-normal">
                            {{ grupo.total }} {{ estatusLabel[grupo.estatus] ?? grupo.estatus }}
                        </Badge>
                    </div>
                </div>

                <div v-if="planta.urgentes > 0" class="flex flex-col gap-1">
                    <p class="text-xs font-medium text-muted-foreground">Prioridades:</p>
                    <p class="flex items-center gap-1 text-sm font-medium text-red-600">
                        <CircleAlert class="size-3.5" />
                        {{ planta.urgentes }} {{ planta.urgentes === 1 ? 'urgente' : 'urgentes' }}
                    </p>
                </div>

                <div class="mt-auto grid grid-cols-2 gap-2 border-t pt-3">
                    <div class="flex items-center gap-1.5 text-sm">
                        <CalendarClock class="size-3.5 text-muted-foreground" />
                        <span class="text-muted-foreground">Programados</span>
                        <span class="ml-auto font-semibold">{{ planta.programados }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-sm">
                        <ShoppingCart class="size-3.5 text-muted-foreground" />
                        <span class="text-muted-foreground">Cotizados</span>
                        <span class="ml-auto font-semibold">{{ planta.cotizados }}</span>
                    </div>
                </div>

                <p class="text-xs text-muted-foreground">
                    {{ planta.folio }} · Creada: {{ planta.creada ?? '—' }}
                </p>
            </Link>

            <p v-if="!plantas.length"
                class="col-span-full rounded-2xl border py-12 text-center text-sm text-muted-foreground">
                Aún no hay plantas registradas.
            </p>
        </div>
    </PageLayout>
</template>
