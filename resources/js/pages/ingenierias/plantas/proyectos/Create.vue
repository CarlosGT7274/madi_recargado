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
import { Head } from '@inertiajs/vue3';
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

const props = defineProps<{
    planta: PlantaRef;
}>();



const tiposProyecto = [
    {
        value: 'grande',
        titulo: 'Proyectos mayores',
        descripcion: 'Flujo completo con levantamiento, planeación y demás procesos para proyectos mayores a $50000.',
    },
    {
        value: 'chico',
        titulo: 'Proyectos menores',
        descripcion: 'Flujo simplificado para capturar actividades, cotización y orden de compra para proyectos menores a $50000',
    },
] as const;


</script>

<template>

    <Head title="Nuevo Proyecto" />

    <PageLayout title="Nuevo Proyecto" :description="`Para la planta ${planta.nombre}`"
        endpoint="ingenierias.plantas.proyectos">
        <template #breadcrumbs>
            <Link :href="PlantaController.show(planta.id)"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <div class="mx-auto max-w-2xl">
            <div class="rounded-2xl border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6 border-b">
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">Datos del proyecto</h3>
                    <p class="text-sm text-muted-foreground">Ingresa la información básica para registrar el proyecto.
                    </p>
                </div>

                <div class="p-6">
                    <Form :action="ProyectoController.store(planta.id).url"
                        :method="ProyectoController.store(planta.id).method" v-slot="{ errors, processing }"
                        class="space-y-6">
                        <div class="space-y-2">
                            <Label for="nombre">Nombre del proyecto <span class="text-red-500">*</span></Label>
                            <Input id="nombre" name="nombre" placeholder="Ej. Instalación de tableros de control"
                                required />
                            <InputError :message="errors.nombre" />
                        </div>

                        <div class="space-y-2">
                            <Label>Tipo de proyecto <span class="text-red-500">*</span></Label>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label v-for="opcion in tiposProyecto" :key="opcion.value"
                                    class="relative flex cursor-pointer flex-col gap-2 rounded-2xl border p-4 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5 hover:bg-accent/50">
                                    <input type="radio" name="tipo" :value="opcion.value"
                                        :checked="opcion.value === 'grande'" class="peer sr-only" required />
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold">{{ opcion.titulo }}</span>
                                        <span
                                            class="flex size-4 shrink-0 items-center justify-center rounded-full border-2 border-muted-foreground/40 peer-checked:border-primary">
                                            <span class="hidden size-2 rounded-full bg-primary peer-checked:block" />
                                        </span>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ opcion.descripcion }}</p>
                                </label>
                            </div>

                            <InputError :message="errors.tipo" />
                        </div>

                        <div class="space-y-2">
                            <Label for="descripcion">Descripción (opcional)</Label>
                            <Textarea id="descripcion" name="descripcion"
                                placeholder="Agrega detalles adicionales del proyecto..." class="resize-none"
                                rows="3" />
                            <InputError :message="errors.descripcion" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link :href="PlantaController.show(planta.id).url">
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
