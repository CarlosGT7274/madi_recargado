<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageLayout from '@/components/PageLayout.vue';

defineProps<{
    planeacion: any;
    asignaciones: any;
    partidasDisponibles: any;
}>();
</script>

<template>
    <Head title="Detalle Planeación" />
    <PageLayout title="Detalle Planeación" description="Detalle pendiente de implementación">
        <div class="rounded-2xl border bg-card p-8 text-center text-muted-foreground shadow-sm">
            <p>El detalle de la planeación está pendiente de implementación.</p>
        </div>
    </PageLayout>
</template>
