<script setup lang="ts">
import type { Component } from 'vue';
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    valor: number | string;
    etiqueta: string;
    descripcion?: string;
    icono: Component;
}>();
</script>

<template>
    <Card>
        <CardContent class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-2xl font-bold tabular-nums">{{ valor }}</p>
                <p class="mt-1 text-sm font-medium">{{ etiqueta }}</p>
                <p v-if="descripcion" class="mt-0.5 truncate text-xs text-muted-foreground">
                    {{ descripcion }}
                </p>
            </div>
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <component :is="icono" class="size-5" />
            </div>
        </CardContent>
    </Card>
</template>
