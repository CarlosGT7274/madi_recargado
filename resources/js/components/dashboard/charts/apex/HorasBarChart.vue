<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

type FilaHorasSemana = {
    anio: number;
    semana: number;
    periodo: string;
    horasTrabajadas: number;
    horasExtra: number;
};

const props = defineProps<{ data: FilaHorasSemana[] }>();

const hayHorasExtra = computed(() => props.data.some((fila) => fila.horasExtra > 0));

const series = computed(() => {
    const base = [{ name: 'Horas trabajadas', data: props.data.map((fila) => fila.horasTrabajadas) }];
    if (hayHorasExtra.value) {
        base.push({ name: 'Horas extra', data: props.data.map((fila) => fila.horasExtra) });
    }
    return base;
});

const options = computed<ApexOptions>(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: 'hsl(var(--muted-foreground))',
        animations: { speed: 300 },
    },
    colors: ['hsl(var(--chart-2))', 'hsl(var(--chart-3))'],
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
        show: hayHorasExtra.value,
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
        title: { text: 'Horas', style: { color: 'hsl(var(--muted-foreground))', fontSize: '11px' } },
        labels: { style: { colors: 'hsl(var(--muted-foreground))', fontSize: '11px' } },
    },
    tooltip: { style: { fontSize: '12px' } },
}));
</script>

<template>
    <VueApexCharts type="bar" height="100%" width="100%" :options="options" :series="series" />
</template>
