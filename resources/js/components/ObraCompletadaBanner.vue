<script setup lang="ts">
import { CheckCircle2 } from '@lucide/vue';

defineProps<{
    montoTotal: number | null;
    descripcion?: string;
}>();

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>
    <div
        class="mb-6 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/30">
        <div class="flex items-start gap-3 p-5">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white">
                <CheckCircle2 class="size-5" />
            </div>
            <div class="flex-1">
                <p class="font-semibold text-blue-800 dark:text-blue-300">Obra completada</p>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                    {{ descripcion ?? 'Al menos una versión tiene orden de compra.' }}
                </p>
                <div class="mt-3 rounded-lg bg-white/60 px-4 py-2 text-sm dark:bg-black/20">
                    Monto total aprobado: <span class="font-semibold">{{ formatoMoneda(montoTotal) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
