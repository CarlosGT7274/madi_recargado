<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsLevantamientoNuevo, type PlantaRef, type ProyectoRef, pageLayout } from '@/lib/breadcrumbs';
import GaleriaImagenes from '@/components/GaleriaImagenes.vue';

interface Props {
    planta: PlantaRef;
    proyecto: ProyectoRef;
}

export default pageLayout(() => {
    const { planta, proyecto } = usePage<Props>().props;
    return breadcrumbsLevantamientoNuevo(planta, proyecto);
});
</script>

<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Download, Upload, Save } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import PageLayout from '@/components/PageLayout.vue';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import LevantamientoForm from './components/LevantamientoForm.vue';
import type { LevantamientoFormData } from './types';

const props = defineProps<{
    planta: { id: number; nombre: string; folio: string };
    proyecto: { id: number; nombre: string; folio: string };
}>();


const page = usePage<{ auth: { user: { name: string } } }>();

const datosIniciales: LevantamientoFormData = {
    folio: '',
    nombre: '',
    cliente: '',
    obra: null,
    solicitante: page.props.auth?.user?.name ?? null,
    fecha_solicitud: new Date().toISOString().slice(0, 10),
    usuario_requiriente: null,
    correo_usuario: null,
    area_trabajo: null,
    titulo_cotizacion: null,
    medio_solicitud: null,
    prioridad: 'normal',
    trabajos_alturas_certificado: false,
    trabajos_alturas_notas: null,
    espacios_confinados_aplica: false,
    espacios_confinados_certificado: false,
    espacios_confinados_notas: null,
    corte_soldadura_aplica: false,
    corte_soldadura_certificado: false,
    corte_soldadura_notas: null,
    izaje_aplica: false,
    izaje_certificado: false,
    izaje_notas: null,
    apertura_lineas_aplica: false,
    apertura_lineas_certificado: false,
    apertura_lineas_notas: null,
    excavacion_aplica: false,
    excavacion_certificado: false,
    excavacion_notas: null,
    notas_maquinaria: null,
    notas_admin: null,
    fecha_levantamiento_programada: null,
    fecha_envio_cotizacion_programada: null,
    fecha_cotizacion_enviada: null,
};

const form = useForm<LevantamientoFormData>(datosIniciales);

function actualizar(payload: LevantamientoFormData) {
    Object.assign(form, payload);
}
const imagenesPendientes = ref<File[]>([]);

function guardar() {
    form.transform((data) => ({ ...data, imagenes: imagenesPendientes.value }))
        .post(LevantamientoController.store({ planta: props.planta.id, proyecto: props.proyecto.id }).url);
}

const archivoInput = ref<HTMLInputElement | null>(null);
const subiendoPlantilla = ref(false);

// Cada fila con error llega como `errors.fila_2`, `errors.fila_5`, etc.
// Los juntamos en una sola lista para pintarlos con AlertError.
const erroresImportacion = computed(() =>
    Object.entries(page.props.errors ?? {})
        .filter(([key]) => key.startsWith('fila_'))
        .map(([, mensaje]) => mensaje as string),
);

function subirPlantilla(): void {
    const archivo = archivoInput.value?.files?.[0];
    if (!archivo) return;

    subiendoPlantilla.value = true;

    router.post(
        LevantamientoController.importar({ planta: props.planta.id, proyecto: props.proyecto.id }).url,
        { archivo },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                subiendoPlantilla.value = false;
                if (archivoInput.value) archivoInput.value.value = '';
            },
        },
    );
}
</script>

<template>

    <Head title="Nuevo Levantamiento" />

    <PageLayout title="Nuevo Levantamiento" description="Captura manual o sube tu plantilla en bulto" endpoint="ingenierias.plantas.proyectos.levantamientos">
        <template #breadcrumbs>
            <Link :href="ProyectoController.show([planta.id, proyecto.id]).url"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <div class="mb-6 rounded-2xl border bg-card p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium">Captura en bulto (plantilla Excel)</p>
                    <p class="text-sm text-muted-foreground">
                        Descarga la plantilla, llénala (una fila por levantamiento) y súbela para crearlos todos de un
                        jalón.
                    </p>
                </div>
                <a :href="LevantamientoController.plantilla({ planta: planta.id, proyecto: proyecto.id }).url">
                    <Button variant="outline">
                        <Download class="mr-2 size-4" />
                        Descargar Plantilla
                    </Button>
                </a>
            </div>

            <label
                class="mt-4 flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-emerald-400 bg-emerald-50 py-6 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                <Upload class="size-4" />
                {{ subiendoPlantilla ? 'Subiendo…' : 'Subir Plantilla Llena' }}
                <input ref="archivoInput" type="file" accept=".xlsx,.xls" class="hidden" :disabled="subiendoPlantilla"
                    @change="subirPlantilla" />
            </label>

            <AlertError v-if="erroresImportacion.length" class="mt-4" title="Algunas filas no se pudieron importar"
                :errors="erroresImportacion" />
        </div>

        <LevantamientoForm mode="create" :data="form" :errors="form.errors" @update="actualizar" />

        <GaleriaImagenes class="mt-6" archivable-type="levantamiento" :archivable-id="null" :imagenes="[]"
            v-model:pendientes="imagenesPendientes" />

        <div class="mt-6 flex justify-end gap-2">
            <Link :href="ProyectoController.show([planta.id, proyecto.id])">
                <Button type="button" variant="ghost">Cancelar</Button>
            </Link>
            <Button :disabled="form.processing" @click="guardar">Guardar Levantamiento</Button>
        </div>
    </PageLayout>
</template>
