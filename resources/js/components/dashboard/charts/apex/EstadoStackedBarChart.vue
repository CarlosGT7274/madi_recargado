<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

type FilaEstadoSemana = {
    anio: number;
    semana: number;
    periodo: string;
    asignado: number;
    en_progreso: number;
    completado: number;
    cancelado: number;
};

const props = defineProps<{ data: FilaEstadoSemana[] }>();

const estados = [
    { key: 'asignado', label: 'Asignado', color: 'hsl(var(--chart-2))' },
    { key: 'en_progreso', label: 'En proceso', color: 'hsl(var(--chart-3))' },
    { key: 'completado', label: 'Completado', color: 'hsl(var(--chart-4))' },
    { key: 'cancelado', label: 'Cancelado', color: 'hsl(var(--chart-5))' },
] as const;

const series = computed(() =>
    estados.map((estado) => ({
        name: estado.label,
        data: props.data.map((fila) => fila[estado.key]),
    })),
);

const options = computed<ApexOptions>(() => ({
    chart: {
        type: 'bar',
        stacked: true,
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: 'hsl(var(--muted-foreground))',
        animations: { speed: 300 },
    },
    colors: estados.map((estado) => estado.color),
    plotOptions: {
        bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '50%' },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: 'hsl(var(--border))',
        strokeDashArray: 3,
        xaxis: { lines: { show: false } },
        padding: { left: 8, right: 16 },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '12px',
        labels: { colors: 'hsl(var(--foreground))' },
    },
    xaxis: {
        categories: props.data.map((fila) => fila.periodo),
        labels: { style: { colors: 'hsl(var(--muted-foreground))', fontSize: '11px' } },
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
    <VueApexCharts type="bar" height="100%" width="100%" :options="options" :series="series" />
</template>
