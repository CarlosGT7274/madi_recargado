<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

type FilaActividadSemana = { anio: number; semana: number; periodo: string; valor: number };

const props = defineProps<{ data: FilaActividadSemana[] }>();

const series = computed(() => [{ name: 'Actividades planeadas', data: props.data.map((fila) => fila.valor) }]);

const options = computed<ApexOptions>(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: 'hsl(var(--muted-foreground))',
        zoom: { enabled: false },
        animations: { speed: 300 },
    },
    colors: ['hsl(var(--chart-1))'],
    stroke: { curve: 'smooth', width: 2.5 },
    fill: {
        type: 'gradient',
        gradient: { opacityFrom: 0.35, opacityTo: 0.02 },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: 'hsl(var(--border))',
        strokeDashArray: 3,
        xaxis: { lines: { show: false } },
        padding: { left: 8, right: 16 },
    },
    xaxis: {
        categories: props.data.map((fila) => fila.periodo),
        labels: { style: { colors: 'hsl(var(--muted-foreground))', fontSize: '11px' }, rotate: 0 },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { style: { colors: 'hsl(var(--muted-foreground))', fontSize: '11px' } },
    },
    tooltip: { style: { fontSize: '12px' } },
}));
</script>

<template>
    <VueApexCharts type="area" height="100%" width="100%" :options="options" :series="series" />
</template>
