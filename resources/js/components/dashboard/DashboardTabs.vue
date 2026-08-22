<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    tabs: { key: string; label: string }[];
    activo: string;
}>();

const emit = defineEmits<{ (e: 'update:activo', key: string): void }>();
</script>

<template>
    <div class="flex w-fit gap-1 rounded-lg bg-muted p-1">
        <Button v-for="tab in tabs" :key="tab.key" type="button" size="sm"
            :variant="activo === tab.key ? 'default' : 'ghost'" :class="activo === tab.key
                    ? ''
                    : 'text-muted-foreground hover:text-foreground'
                " @click="emit('update:activo', tab.key)">
            {{ tab.label }}
        </Button>
    </div>
</template>
