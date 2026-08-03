<script setup lang="ts">
defineProps<{ modelValue: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>();
</script>

<template>
    <div class="inline-flex overflow-hidden rounded-md border">
        <button
            type="button"
            class="px-3 py-1 text-xs font-semibold transition-colors"
            :class="modelValue ? 'bg-emerald-500 text-white' : 'bg-muted text-muted-foreground hover:bg-accent'"
            @click="emit('update:modelValue', true)"
        >
            SI
        </button>
        <button
            type="button"
            class="px-3 py-1 text-xs font-semibold transition-colors"
            :class="!modelValue ? 'bg-red-500 text-white' : 'bg-muted text-muted-foreground hover:bg-accent'"
            @click="emit('update:modelValue', false)"
        >
            NO
        </button>
    </div>
</template>
