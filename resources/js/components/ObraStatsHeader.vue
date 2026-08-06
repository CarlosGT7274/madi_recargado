<script setup lang="ts">
import { CheckCircle2, FileText, Package } from '@lucide/vue';

defineProps<{
    obra: string;
    completada: boolean;
    totalVersiones: number;
    versionesAprobadas: number;
    montoTotal: number | null;
}>();

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border shadow-sm">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6 text-white">
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-2xl font-bold">{{ obra }}</p>
                <span class="rounded-full px-3 py-1 text-xs font-bold uppercase"
                    :class="completada ? 'bg-emerald-500' : 'bg-white/20'">
                    {{ completada ? 'Completado' : 'En proceso' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-blue-100">
                {{ totalVersiones }} {{ totalVersiones === 1 ? 'versión' : 'versiones' }}
            </p>
        </div>

        <div class="grid grid-cols-1 divide-y border-b bg-card sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="flex items-center gap-3 p-5">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600">
                    <FileText class="size-4" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Cotizaciones Totales</p>
                    <p class="text-xl font-bold">{{ totalVersiones }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-5">
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                    <CheckCircle2 class="size-4" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Cotizaciones Aprobadas</p>
                    <p class="text-xl font-bold">{{ versionesAprobadas }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ totalVersiones ? Math.round((versionesAprobadas / totalVersiones) * 100) : 0 }}% del total
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-5">
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600">
                    <Package class="size-4" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Monto Total Aprobado</p>
                    <p class="text-xl font-bold">{{ formatoMoneda(montoTotal) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
