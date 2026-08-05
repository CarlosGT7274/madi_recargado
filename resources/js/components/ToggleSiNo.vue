<script setup lang="ts">
withDefaults(defineProps<{ modelValue: boolean; readonly?: boolean }>(), {
    readonly: false,
});
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>();
</script>

<template>
    <div class="inline-flex overflow-hidden rounded-md border">
        <button type="button" :disabled="readonly" class="px-3 py-1 text-xs font-semibold transition-colors" :class="[
            modelValue ? 'bg-emerald-500 text-white' : 'bg-muted text-muted-foreground',
            !readonly && !modelValue ? 'hover:bg-accent' : '',
            readonly ? 'cursor-default' : '',
        ]" @click="!readonly && emit('update:modelValue', true)">
            SI
        </button>
        <button type="button" :disabled="readonly" class="px-3 py-1 text-xs font-semibold transition-colors" :class="[
            !modelValue ? 'bg-red-500 text-white' : 'bg-muted text-muted-foreground',
            !readonly && modelValue ? 'hover:bg-accent' : '',
            readonly ? 'cursor-default' : '',
        ]" @click="!readonly && emit('update:modelValue', false)">
            NO
        </button>
    </div>
</template>
