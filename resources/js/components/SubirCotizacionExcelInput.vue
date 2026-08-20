<script setup lang="ts">
import { ref } from 'vue';
import { Upload } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import ExcelPreviewDialog from '@/components/ExcelPreviewDialog.vue';

/**
 * Único punto de entrada para subir un Excel de cotización en todo el
 * sistema. Encapsula: input file + validación en el navegador
 * (useExcelCotizacionValidator vía ExcelPreviewDialog) + confirmación.
 * Solo emite 'archivo-validado' cuando el Excel ya pasó el preview sin
 * errores — el padre nunca ve el archivo crudo del input.
 *
 * Reemplaza la lógica duplicada que antes vivía en:
 * VersionesCotizacion.vue, levantamientos/Show.vue, proyectos/Show.vue.
 */
const props = withDefaults(
    defineProps<{
        label?: string;
        variant?: 'default' | 'outline' | 'secondary';
        size?: 'default' | 'sm' | 'lg';
        disabled?: boolean;
    }>(),
    {
        label: 'Subir cotización',
        variant: 'default',
        size: 'default',
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'archivo-validado', archivo: File): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const previewOpen = ref(false);
const archivoPendiente = ref<File | null>(null);

function onChange(): void {
    const archivo = inputRef.value?.files?.[0];
    if (!archivo) return;

    archivoPendiente.value = archivo;
    previewOpen.value = true;

    if (inputRef.value) inputRef.value.value = '';
}

function handleConfirm(archivo: File): void {
    emit('archivo-validado', archivo);
    archivoPendiente.value = null;
}

function handleCancel(): void {
    archivoPendiente.value = null;
}
</script>

<template>
    <label :class="disabled ? 'pointer-events-none opacity-50' : 'cursor-pointer'">
        <Button as="span" :variant="variant" :size="size" :disabled="disabled">
            <Upload class="mr-2 size-4" />
            {{ label }}
        </Button>
        <input ref="inputRef" type="file" accept=".xlsx,.xls" class="hidden" :disabled="disabled" @change="onChange" />
    </label>

    <ExcelPreviewDialog v-model:open="previewOpen" :archivo="archivoPendiente" @confirm="handleConfirm"
        @cancel="handleCancel" />
</template>
