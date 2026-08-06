<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsObraDirecto, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
    grupo: { obra: string };
}

export default pageLayout(() => {
    const { planta, proyecto, grupo } = usePage<Props>().props;
    return breadcrumbsObraDirecto(planta, proyecto, grupo.obra);
});
</script>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import ArchivoController from '@/actions/App/Http/Controllers/ArchivoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import ObraCompletadaBanner from '@/components/ObraCompletadaBanner.vue';
import ObraStatsHeader from '@/components/ObraStatsHeader.vue';
import PageLayout from '@/components/PageLayout.vue';
import VersionesCotizacion, { type VersionCotizacion } from '@/components/VersionesCotizacion.vue';
import { usePermissions } from '@/composables/usePermissions';

interface PlantaRef { id: number; nombre: string }
interface ProyectoRef { id: number; nombre: string; folio: string }

interface Grupo {
    obra: string;
    completada: boolean;
    montoCompletado: number | null;
    totalVersiones: number;
    versiones: VersionCotizacion[];
}

const props = defineProps<{
    planta: PlantaRef;
    proyecto: ProyectoRef;
    grupo: Grupo;
}>();

const { hasPermission, Accion } = usePermissions();
const endpoint = 'ingenierias.plantas.proyectos.cotizaciones';
const puedeCrear = hasPermission(endpoint, Accion.CREATE);

const rutaCotizaciones = computed(() => ({
    planta: props.planta.id,
    proyecto: props.proyecto.id,
}));

const versionesAprobadas = computed(
    () => props.grupo.versiones.filter((v) => v.completada).length,
);

function subirNuevaVersion(archivo: File): void {
    router.post(
        CotizacionController.storeProyecto(rutaCotizaciones.value).url,
        { archivo },
        { forceFormData: true, preserveScroll: true },
    );
}

function subirExcelVersion(versionId: number, archivo: File): void {
    router.post(
        ArchivoController.storeDocumento().url,
        {
            archivable_type: 'cotizacion',
            archivable_id: versionId,
            archivo,
        },
        { forceFormData: true, preserveScroll: true },
    );
}

function detalleHref(versionId: number): string {
    return CotizacionController.showProyecto({
        planta: props.planta.id,
        proyecto: props.proyecto.id,
        cotizacion: versionId,
    }).url;
}
</script>

<template>

    <Head :title="`${grupo.obra} — Cotizaciones`" />

    <PageLayout title="" description="">
        <ObraCompletadaBanner v-if="grupo.completada" :monto-total="grupo.montoCompletado" />

        <ObraStatsHeader :obra="grupo.obra" :completada="grupo.completada" :total-versiones="grupo.totalVersiones"
            :versiones-aprobadas="versionesAprobadas" :monto-total="grupo.montoCompletado" />

        <VersionesCotizacion :versiones="grupo.versiones" :puede-crear="puedeCrear"
            :plantilla-url="CotizacionController.plantillaProyecto(rutaCotizaciones).url"
            :permitir-subir-excel-por-version="true" :detalle-href="detalleHref" @nueva-version="subirNuevaVersion"
            @subir-excel-version="subirExcelVersion" />
    </PageLayout>
</template>
