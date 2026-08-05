<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ImageOff, Trash2, Upload, X, ZoomIn, ZoomOut, Download } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, onUnmounted, ref, watch } from 'vue';
import ArchivoController from '@/actions/App/Http/Controllers/ArchivoController';

export interface ImagenResumen {
    id: number;
    url: string;
    nombreArchivo: string | null;
}

const props = defineProps<{
    archivableType: string;
    archivableId?: number | null;
    imagenes: ImagenResumen[];
    soloLectura?: boolean;
}>();

const montado = ref(false);

const pendientes = defineModel<File[]>('pendientes', { default: () => [] });

const modoLocal = props.archivableId === null || props.archivableId === undefined;

const inputArchivos = ref<HTMLInputElement | null>(null);
const subiendo = ref(false);
const previewsLocales = ref<string[]>([]);

function onSeleccion(): void {
    const archivos = inputArchivos.value?.files;
    if (!archivos || archivos.length === 0) return;

    if (modoLocal) {
        const nuevos = Array.from(archivos);
        pendientes.value = [...pendientes.value, ...nuevos];
        previewsLocales.value = [...previewsLocales.value, ...nuevos.map((f) => URL.createObjectURL(f))];
        if (inputArchivos.value) inputArchivos.value.value = '';
        return;
    }

    subir(archivos);
}

function subir(archivos: FileList): void {
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

function quitarLocal(idx: number): void {
    URL.revokeObjectURL(previewsLocales.value[idx]);
    previewsLocales.value.splice(idx, 1);
    const nuevos = [...pendientes.value];
    nuevos.splice(idx, 1);
    pendientes.value = nuevos;
}

// --- Lista unificada para el lightbox (sirve para ambos modos) ---
type ItemGaleria = { src: string; nombre: string | null; eliminable: boolean; onEliminar: () => void };

const items = computed<ItemGaleria[]>(() => {
    if (modoLocal) {
        return previewsLocales.value.map((src, idx) => ({
            src,
            nombre: pendientes.value[idx]?.name ?? null,
            eliminable: !props.soloLectura,
            onEliminar: () => quitarLocal(idx),
        }));
    }
    return props.imagenes.map((img) => ({
        src: img.url,
        nombre: img.nombreArchivo,
        eliminable: !props.soloLectura,
        onEliminar: () => eliminar(img.id),
    }));
});

// --- Lightbox ---
const indiceActivo = ref<number | null>(null);
const zoom = ref(1);
const pan = ref({ x: 0, y: 0 });
const arrastrando = ref(false);
const origenArrastre = ref({ x: 0, y: 0 });

const abierto = computed(() => indiceActivo.value !== null);
const itemActivo = computed(() => (indiceActivo.value !== null ? items.value[indiceActivo.value] : null));

function abrir(idx: number): void {
    indiceActivo.value = idx;
    resetZoom();
}

function cerrar(): void {
    indiceActivo.value = null;
    resetZoom();
}

function resetZoom(): void {
    zoom.value = 1;
    pan.value = { x: 0, y: 0 };
}

function siguiente(): void {
    if (indiceActivo.value === null) return;
    indiceActivo.value = (indiceActivo.value + 1) % items.value.length;
    resetZoom();
}

function anterior(): void {
    if (indiceActivo.value === null) return;
    indiceActivo.value = (indiceActivo.value - 1 + items.value.length) % items.value.length;
    resetZoom();
}

function acercar(): void {
    zoom.value = Math.min(zoom.value + 0.5, 4);
}

function alejar(): void {
    zoom.value = Math.max(zoom.value - 0.5, 1);
    if (zoom.value === 1) pan.value = { x: 0, y: 0 };
}

function alDobleClick(): void {
    if (zoom.value > 1) {
        resetZoom();
    } else {
        zoom.value = 2;
    }
}

function alRueda(e: WheelEvent): void {
    e.preventDefault();
    if (e.deltaY < 0) acercar();
    else alejar();
}

function iniciarArrastre(e: MouseEvent): void {
    if (zoom.value === 1) return;
    arrastrando.value = true;
    origenArrastre.value = { x: e.clientX - pan.value.x, y: e.clientY - pan.value.y };
}

function alMover(e: MouseEvent): void {
    if (!arrastrando.value) return;
    pan.value = { x: e.clientX - origenArrastre.value.x, y: e.clientY - origenArrastre.value.y };
}

function detenerArrastre(): void {
    arrastrando.value = false;
}

function alTeclado(e: KeyboardEvent): void {
    if (!abierto.value) return;
    if (e.key === 'Escape') cerrar();
    if (e.key === 'ArrowRight') siguiente();
    if (e.key === 'ArrowLeft') anterior();
    if (e.key === '+' || e.key === '=') acercar();
    if (e.key === '-') alejar();
}

onMounted(() => window.addEventListener('keydown', alTeclado));
onMounted(() => (montado.value = true));
onUnmounted(() => window.removeEventListener('keydown', alTeclado));

// Si borran la imagen activa desde el lightbox, cierra o reajusta el índice.
watch(items, (nuevos) => {
    if (indiceActivo.value === null) return;
    if (nuevos.length === 0) {
        cerrar();
    } else if (indiceActivo.value >= nuevos.length) {
        indiceActivo.value = nuevos.length - 1;
        resetZoom();
    }
});

onBeforeUnmount(() => {
    previewsLocales.value.forEach((url) => URL.revokeObjectURL(url));
});
</script>

<template>
    <div class="rounded-2xl border bg-card p-5 shadow-sm">
        <p class="mb-4 text-sm font-medium text-muted-foreground">Imágenes ({{ items.length }})</p>

        <label v-if="!soloLectura"
            class="mb-4 flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed py-6 text-sm font-medium text-muted-foreground hover:bg-accent">
            <Upload class="size-4" />
            {{ subiendo ? 'Subiendo…' : 'Arrastra imágenes aquí o haz clic para seleccionar' }}
            <input ref="inputArchivos" type="file" accept="image/*" multiple class="hidden" :disabled="subiendo"
                @change="onSeleccion" />
        </label>

        <div v-if="items.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            <button v-for="(item, idx) in items" :key="idx" type="button"
                class="group relative aspect-square overflow-hidden rounded-lg border" @click="abrir(idx)">
                <img :src="item.src" :alt="item.nombre ?? ''"
                    class="size-full object-cover transition-transform duration-200 group-hover:scale-105" />
                <div
                    class="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/20">
                    <ZoomIn class="size-5 text-white opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
                <button v-if="item.eliminable" type="button"
                    class="absolute right-1 top-1 rounded-full bg-destructive/90 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                    @click.stop="item.onEliminar()">
                    <Trash2 class="size-3.5" />
                </button>
            </button>
        </div>
        <div v-else class="flex flex-col items-center gap-2 py-8 text-center text-sm text-muted-foreground">
            <ImageOff class="size-6" />
            Aún no hay imágenes.
        </div>

        <!-- Lightbox -->
        <Teleport v-if="montado" to="body">
            <div v-if="abierto && itemActivo" class="fixed inset-0 z-50 flex flex-col bg-black/95" @click.self="cerrar">
                <!-- Barra superior -->
                <div class="flex items-center justify-between px-4 py-3 text-white">
                    <span class="truncate text-sm text-white/70">
                        {{ itemActivo.nombre ?? '' }}
                        <span class="ml-2 text-white/40">{{ (indiceActivo ?? 0) + 1 }} / {{ items.length }}</span>
                    </span>
                    <div class="flex items-center gap-1">
                        <button type="button" class="rounded-full p-2 hover:bg-white/10" title="Alejar" @click="alejar">
                            <ZoomOut class="size-5" />
                        </button>
                        <button type="button" class="rounded-full p-2 hover:bg-white/10" title="Acercar"
                            @click="acercar">
                            <ZoomIn class="size-5" />
                        </button>
                        <a :href="itemActivo.src" :download="itemActivo.nombre ?? undefined" target="_blank"
                            rel="noopener">
                            <button type="button" class="rounded-full p-2 hover:bg-white/10" title="Descargar">
                                <Download class="size-5" />
                            </button>
                        </a>
                        <button v-if="itemActivo.eliminable" type="button" class="rounded-full p-2 hover:bg-white/10"
                            title="Eliminar" @click="itemActivo.onEliminar()">
                            <Trash2 class="size-5" />
                        </button>
                        <button type="button" class="rounded-full p-2 hover:bg-white/10" title="Cerrar (Esc)"
                            @click="cerrar">
                            <X class="size-5" />
                        </button>
                    </div>
                </div>

                <!-- Imagen -->
                <div class="relative flex flex-1 items-center justify-center overflow-hidden select-none"
                    @wheel="alRueda" @mousedown="iniciarArrastre" @mousemove="alMover" @mouseup="detenerArrastre"
                    @mouseleave="detenerArrastre">
                    <img :src="itemActivo.src" :alt="itemActivo.nombre ?? ''"
                        class="max-h-full max-w-full object-contain transition-transform duration-150"
                        :class="zoom > 1 ? (arrastrando ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                        :style="{ transform: `translate(${pan.x}px, ${pan.y}px) scale(${zoom})` }"
                        @dblclick="alDobleClick" @dragstart.prevent />

                    <button v-if="items.length > 1" type="button"
                        class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white hover:bg-black/60"
                        @click.stop="anterior">
                        <ChevronLeft class="size-6" />
                    </button>
                    <button v-if="items.length > 1" type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white hover:bg-black/60"
                        @click.stop="siguiente">
                        <ChevronRight class="size-6" />
                    </button>
                </div>

                <!-- Tira de miniaturas -->
                <div v-if="items.length > 1" class="flex gap-2 overflow-x-auto px-4 py-3">
                    <button v-for="(item, idx) in items" :key="idx" type="button"
                        class="size-14 shrink-0 overflow-hidden rounded-md border-2 transition-colors"
                        :class="idx === indiceActivo ? 'border-white' : 'border-transparent opacity-60 hover:opacity-100'"
                        @click="abrir(idx)">
                        <img :src="item.src" class="size-full object-cover" />
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
