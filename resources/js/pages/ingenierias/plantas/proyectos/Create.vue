<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsPlanta, type PlantaRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
}

export default pageLayout(() => [
    ...breadcrumbsPlanta(usePage<Props>().props.planta),
    { title: 'Nuevo Proyecto', href: '' }
]);
</script>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Form } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import PageLayout from '@/components/PageLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    planta: PlantaRef;
}>();

const form = useForm({
    nombre: '',
    tipo: 'grande',
    descripcion: '',
});

function submit() {
    form.post(ProyectoController.store(props.planta.id).url);
}
</script>

<template>
    <Head title="Nuevo Proyecto" />

    <PageLayout
        title="Nuevo Proyecto"
        :description="`Para la planta ${planta.nombre}`"
        endpoint="ingenierias.plantas.proyectos"
    >
        <template #breadcrumbs>
            <Link
                :href="PlantaController.show(planta.id)"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <div class="mx-auto max-w-2xl">
            <div class="rounded-2xl border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6 border-b">
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">Datos del proyecto</h3>
                    <p class="text-sm text-muted-foreground">Ingresa la información básica para registrar el proyecto.</p>
                </div>
                
                <div class="p-6">
                    <Form
                        v-bind="ProyectoController.store.form(planta.id)"
                        @success="() => {}"
                        v-slot="{ errors, processing }"
                        class="space-y-6"
                    >
                        <div class="space-y-2">
                            <Label for="nombre">Nombre del proyecto <span class="text-red-500">*</span></Label>
                            <Input
                                id="nombre"
                                name="nombre"
                                v-model="form.nombre"
                                placeholder="Ej. Instalación de tableros de control"
                                required
                            />
                            <InputError :message="errors.nombre" />
                        </div>

                        <div class="space-y-2">
                            <Label for="tipo">Tipo de flujo <span class="text-red-500">*</span></Label>
                            <Select name="tipo" v-model="form.tipo">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona un tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="grande">Proyecto con Levantamiento (Grande)</SelectItem>
                                    <SelectItem value="chico">Directo a Actividades (Chico)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">
                                "Con Levantamiento" requerirá crear un levantamiento antes de cotizar. "Directo" pasa directo a actividades.
                            </p>
                            <InputError :message="errors.tipo" />
                        </div>

                        <div class="space-y-2">
                            <Label for="descripcion">Descripción (opcional)</Label>
                            <Textarea
                                id="descripcion"
                                name="descripcion"
                                v-model="form.descripcion"
                                placeholder="Agrega detalles adicionales del proyecto..."
                                class="resize-none"
                                rows="3"
                            />
                            <InputError :message="errors.descripcion" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link :href="PlantaController.show(planta.id)">
                                <Button type="button" variant="ghost">Cancelar</Button>
                            </Link>
                            <Button type="submit" :disabled="processing">
                                <Save class="mr-2 size-4" />
                                Crear Proyecto
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
