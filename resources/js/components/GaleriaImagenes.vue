<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ImageOff, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';
import ArchivoController from '@/actions/App/Http/Controllers/ArchivoController';

export interface ImagenResumen {
    id: number;
    url: string;
    nombreArchivo: string | null;
}

const props = defineProps<{
    archivableType: string;
    archivableId: number;
    imagenes: ImagenResumen[];
    soloLectura?: boolean;
}>();

const inputArchivos = ref<HTMLInputElement | null>(null);
const subiendo = ref(false);

function subir(): void {
    const archivos = inputArchivos.value?.files;
    if (!archivos || archivos.length === 0) return;

    const formData = new FormData();
    formData.append('archivable_type', props.archivableType);
    formData.append('archivable_id', String(props.archivableId));
    Array.from(archivos).forEach((archivo) => formData.append('imagenes[]', archivo));

    subiendo.value = true;
    router.post(ArchivoController.store().url, formData, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            subiendo.value = false;
            if (inputArchivos.value) inputArchivos.value.value = '';
        },
    });
}

function eliminar(id: number): void {
    if (!confirm('¿Eliminar esta imagen?')) return;
    router.delete(ArchivoController.destroy(id).url, { preserveScroll: true });
}
</script>

<template>
    <div class="rounded-2xl border bg-card p-5 shadow-sm">
        <p class="mb-4 text-sm font-medium text-muted-foreground">Imágenes ({{ imagenes.length }})</p>

        <label
            v-if="!soloLectura"
            class="mb-4 flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed py-6 text-sm font-medium text-muted-foreground hover:bg-accent"
        >
            <Upload class="size-4" />
            {{ subiendo ? 'Subiendo…' : 'Arrastra imágenes aquí o haz clic para seleccionar' }}
            <input
                ref="inputArchivos"
                type="file"
                accept="image/*"
                multiple
                class="hidden"
                :disabled="subiendo"
                @change="subir"
            />
        </label>

        <div v-if="imagenes.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            <div
                v-for="imagen in imagenes"
                :key="imagen.id"
                class="group relative aspect-square overflow-hidden rounded-lg border"
            >
                <img :src="imagen.url" :alt="imagen.nombreArchivo ?? ''" class="size-full object-cover" />
                <button
                    v-if="!soloLectura"
                    type="button"
                    class="absolute right-1 top-1 rounded-full bg-destructive/90 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                    @click="eliminar(imagen.id)"
                >
                    <Trash2 class="size-3.5" />
                </button>
            </div>
        </div>

        <div v-else class="flex flex-col items-center gap-2 py-8 text-center text-sm text-muted-foreground">
            <ImageOff class="size-6" />
            Aún no hay imágenes.
        </div>
    </div>
</template>
