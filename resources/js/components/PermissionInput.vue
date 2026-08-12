<script setup lang="ts">
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        endpoint: string;
        accion: string;
        modelValue: string | number;
        type?: string;
        step?: string;
        min?: string;
        class?: string;
        placeholder?: string;
    }>(),
    { type: 'text' },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'blur'): void;
}>();

const { hasPermission } = usePermissions();
const permitido = computed(() => hasPermission(props.endpoint, props.accion));
</script>

<template>
    <Input :type="type" :step="step" :min="min" :placeholder="placeholder" :model-value="modelValue"
        :disabled="!permitido" :title="!permitido ? 'No tienes permiso para editar este campo' : undefined"
        :class="cn('h-8 text-sm', !permitido && 'cursor-not-allowed opacity-60', props.class)"
        @update:model-value="(v) => emit('update:modelValue', v as string)" @blur="emit('blur')" />
</template>
