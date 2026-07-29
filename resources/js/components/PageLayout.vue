<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Plus, Pencil, Trash } from '@lucide/vue';

type Props = {
    title: string;
    description?: string;
    endpoint?: string;
    withCreate?: boolean;
    withEdit?: boolean;
    withDelete?: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'create'): void;
    (e: 'edit'): void;
    (e: 'delete'): void;
}>();

const { hasPermission, Accion } = usePermissions();

function can(accion: number): boolean {
    if (!props.endpoint) return true; // If no endpoint is provided, don't block visually
    return hasPermission(props.endpoint, accion);
}
</script>

<template>
    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <slot name="breadcrumbs"></slot>
                <Heading :title="title" :description="description" />
            </div>

            <div class="flex items-center gap-2">
                <!-- Standard Actions -->
                <Button 
                    v-if="withCreate && can(Accion.CREATE)" 
                    @click="emit('create')"
                >
                    <Plus class="mr-2 size-4" />
                    Nuevo
                </Button>

                <Button 
                    v-if="withEdit && can(Accion.UPDATE)" 
                    variant="outline" 
                    @click="emit('edit')"
                >
                    <Pencil class="mr-2 size-4" />
                    Editar
                </Button>

                <Button 
                    v-if="withDelete && can(Accion.DELETE)" 
                    variant="destructive" 
                    @click="emit('delete')"
                >
                    <Trash class="mr-2 size-4" />
                    Eliminar
                </Button>

                <!-- Custom Actions -->
                <slot name="actions"></slot>
            </div>
        </div>

        <slot></slot>
    </div>
</template>
