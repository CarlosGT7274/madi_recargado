<script setup lang="ts">
import { PackageOpen } from '@lucide/vue';

defineProps<{
    titulo?: string;
    detalle: string;
}>();
</script>

<template>
    <div class="flex h-full min-h-[260px] flex-col items-center justify-center gap-2 text-center">
        <PackageOpen class="size-8 text-muted-foreground" />
        <p class="text-sm font-medium">
            {{ titulo ?? 'Sin datos disponibles' }}
        </p>
        <p class="max-w-sm text-sm text-muted-foreground">{{ detalle }}</p>
    </div>
</template>
