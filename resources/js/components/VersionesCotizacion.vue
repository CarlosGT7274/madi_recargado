<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Download, FileText } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import SubirCotizacionExcelInput from '@/components/SubirCotizacionExcelInput.vue';

export interface VersionCotizacion {
    id: number;
    folio: string;
    fecha: string | null;
    total: number | null;
    estado: string;
    completada: boolean;
    tienePartidas: boolean;
    tieneInsumos: boolean;
    tieneAutorizacion: boolean;
    archivoExcelUrl: string | null;
}

const props = defineProps<{
    versiones: VersionCotizacion[];
    puedeCrear: boolean;
    detalleHref: (versionId: number) => string;
    plantillaUrl?: string;
    permitirSubirExcelPorVersion?: boolean;
    mostrarEstadoInsumos?: boolean;
}>();

const emit = defineEmits<{
    (e: 'nueva-version', archivo: File): void;
    (e: 'subir-excel-version', versionId: number, archivo: File): void;
}>();

function onNuevaVersion(archivo: File): void {
    emit('nueva-version', archivo);
}

function onSubirExcelVersion(versionId: number, archivo: File): void {
    emit('subir-excel-version', versionId, archivo);
}

const estadoLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    rechazada: 'Rechazada',
};

function badgeClase(version: VersionCotizacion): string {
    if (version.completada) return 'bg-emerald-500/10 text-emerald-600';
    if (version.estado === 'rechazada') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function badgeTexto(version: VersionCotizacion): string {
    if (version.completada) return 'Completada';
    return estadoLabel[version.estado] ?? version.estado;
}

function formatoMoneda(valor: number | null): string {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}

function detalleTexto(version: VersionCotizacion): string {
    const partes = [version.tienePartidas ? 'Con partidas' : 'Sin partidas'];
    if (props.mostrarEstadoInsumos) {
        partes.push(version.tieneInsumos ? 'Con insumos' : 'Sin insumos');
    }
    return partes.join(' · ');
}
</script>

<template>
    <div class="mt-6 rounded-2xl border bg-card p-6 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-lg font-semibold">Versiones de esta Obra</p>
                <p class="text-sm text-muted-foreground">Gestiona y revisa todas las cotizaciones</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a v-if="plantillaUrl" :href="plantillaUrl">
                    <Button variant="outline" size="sm">
                        <Download class="mr-2 size-4" />
                        Descargar Plantilla
                    </Button>
                </a>

                <SubirCotizacionExcelInput v-if="puedeCrear" label="Subir Cotizacion" size="sm"
                    @archivo-validado="onNuevaVersion" />
            </div>
        </div>

        <div class="space-y-3">
            <div v-for="version in versiones" :key="version.id"
                class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                :class="version.completada ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'bg-card'">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                        :class="version.completada ? 'bg-emerald-500/10 text-emerald-600' : 'bg-primary/10 text-primary'">
                        <FileText class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold">{{ version.folio }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                :class="badgeClase(version)">
                                {{ badgeTexto(version) }}
                            </span>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span>{{ version.fecha ?? '—' }}</span>
                            <span class="font-medium text-foreground">{{ formatoMoneda(version.total) }}</span>
                            <span>{{ detalleTexto(version) }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <SubirCotizacionExcelInput v-if="permitirSubirExcelPorVersion && puedeCrear"
                        label="Guardar cotización de cliente" variant="outline" size="sm"
                        @archivo-validado="(archivo) => onSubirExcelVersion(version.id, archivo)" />

                    <a v-if="version.archivoExcelUrl" :href="version.archivoExcelUrl" target="_blank">
                        <Button variant="outline" size="sm">
                            <Download class="mr-1.5 size-3.5" />
                            Descargar Excel
                        </Button>
                    </a>

                    <Link :href="detalleHref(version.id)">
                        <Button size="sm" class="bg-violet-600 text-white hover:bg-violet-700">Ver Detalle</Button>
                    </Link>
                </div>
            </div>

            <p v-if="!versiones.length" class="rounded-xl border py-8 text-center text-sm text-muted-foreground">
                Aún no hay versiones para esta obra.
            </p>
        </div>
    </div>
</template>
