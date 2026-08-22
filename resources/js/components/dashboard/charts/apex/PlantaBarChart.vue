<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

type FilaPlanta = { planta: string; valor: number };

const props = withDefaults(
    defineProps<{
        data: FilaPlanta[];
        color?: string;
        valueFormatter?: (valor: number) => string;
    }>(),
    {
        color: 'hsl(var(--chart-1))',
        valueFormatter: (valor: number) => `${valor}`,
    },
);

const datosOrdenados = computed<FilaPlanta[]>(() => [...props.data].sort((a, b) => b.valor - a.valor));

const altura = computed(() => Math.max(280, datosOrdenados.value.length * 48));

const series = computed(() => [{ name: 'Valor', data: datosOrdenados.value.map((fila) => fila.valor) }]);

const options = computed<ApexOptions>(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: 'hsl(var(--muted-foreground))',
        animations: { speed: 300 },
    },
    colors: [props.color],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            borderRadiusApplication: 'end',
            barHeight: '55%',
        },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: 'hsl(var(--border))',
        strokeDashArray: 3,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
        padding: { left: 8, right: 16, top: -8 },
    },
    xaxis: {
        categories: datosOrdenados.value.map((fila) => fila.planta),
        labels: {
            formatter: (valor: string) => props.valueFormatter(Number(valor)),
            style: { colors: 'hsl(var(--muted-foreground))', fontSize: '11px' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { style: { colors: 'hsl(var(--foreground))', fontSize: '12px' } },
    },
    tooltip: {
        y: { formatter: (valor: number) => props.valueFormatter(valor) },
        style: { fontSize: '12px' },
    },
}));
</script>

<template>
    <VueApexCharts type="bar" :height="altura" width="100%" :options="options" :series="series" />
</template>
