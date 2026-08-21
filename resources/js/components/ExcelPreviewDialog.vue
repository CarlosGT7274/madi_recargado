<script setup lang="ts">
import { watch } from 'vue';
import { AlertCircle, CheckCircle2, FileSpreadsheet, Loader2, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useExcelCotizacionValidator, type HeaderFieldError } from '@/composables/useExcelCotizacionValidator';

const props = defineProps<{
    open: boolean;
    archivo: File | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', archivo: File): void;
    (e: 'cancel'): void;
}>();

const { state, tieneErrores, totalErrores, parsearArchivo, reset } = useExcelCotizacionValidator();

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.archivo) {
            parsearArchivo(props.archivo);
        } else if (!isOpen) {
            reset();
        }
    },
);

function handleOpenChange(val: boolean) {
    emit('update:open', val);
    if (!val) emit('cancel');
}

function handleConfirm() {
    // Doble candado: aunque el botón ya esté disabled cuando hay errores,
    // esta guarda evita que un click "colado" (ej. Enter en un input)
    // dispare el submit mientras el resultado sigue siendo inválido.
    if (!props.archivo || tieneErrores.value) return;

    emit('confirm', props.archivo);
    emit('update:open', false);
}

function handleCancel() {
    emit('cancel');
    emit('update:open', false);
}

function erroresDeCelda(errores: any[], columna: string): string {
    const err = errores.find(e => e.columna === columna);
    return err ? err.mensaje : '';
}

function erroresDeCampo(errores: HeaderFieldError[], campo: string): string {
    const err = errores.find(e => e.campo === campo);
    return err ? err.mensaje : '';
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent
            class="flex mx-auto w-[95vw] sm:max-w-[1400px] flex-col gap-0 overflow-hidden p-0 sm:rounded-2xl">
            <DialogHeader class="shrink-0 border-b px-6 py-4">
                <DialogTitle class="flex items-center gap-2">
                    <FileSpreadsheet class="size-5 text-primary" />
                    Validación del Excel
                </DialogTitle>
                <DialogDescription>
                    Revisión de formato y estructura antes de subir la cotización. Corrige los errores marcados en rojo
                    y vuelve a cargar el archivo.
                </DialogDescription>
            </DialogHeader>

            <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden px-6 py-4">
                <div v-if="state.cargando"
                    class="flex flex-1 flex-col items-center justify-center text-muted-foreground">
                    <Loader2 class="size-8 animate-spin mb-4" />
                    <p>Leyendo archivo...</p>
                </div>

                <div v-else-if="state.errorLectura" class="rounded-lg bg-red-50 p-4 text-red-900 border border-red-200">
                    <div class="flex items-center gap-2 font-medium mb-1">
                        <X class="size-5 text-red-500" /> Error al leer el archivo
                    </div>
                    <p class="text-sm">{{ state.errorLectura }}</p>
                </div>

                <template v-else-if="state.resultado">
                    <!-- Resumen del encabezado, con error visible debajo de cada campo -->
                    <div
                        class="grid shrink-0 grid-cols-2 gap-4 rounded-lg border bg-muted/50 p-3 text-sm md:grid-cols-4">
                        <div
                            :class="{ 'rounded-md ring-1 ring-red-300 bg-red-50/60 -m-1 p-1': erroresDeCampo(state.resultado.erroresEncabezado, 'obra') }">
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Obra</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.obra || '—' }}</span>
                        </div>
                        <div
                            :class="{ 'rounded-md ring-1 ring-red-300 bg-red-50/60 -m-1 p-1': erroresDeCampo(state.resultado.erroresEncabezado, 'cliente') }">
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Cliente</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.cliente || '—'
                            }}</span>
                            <span v-if="erroresDeCampo(state.resultado.erroresEncabezado, 'cliente')"
                                class="mt-1 flex items-start gap-1 text-[11px] leading-tight text-red-600">
                                <AlertCircle class="size-3 shrink-0 mt-0.5" />
                                {{ erroresDeCampo(state.resultado.erroresEncabezado, 'cliente') }}
                            </span>
                        </div>
                        <div
                            :class="{ 'rounded-md ring-1 ring-red-300 bg-red-50/60 -m-1 p-1': erroresDeCampo(state.resultado.erroresEncabezado, 'fecha') }">
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Fecha</span>
                            <!-- Se muestra la fecha ya normalizada (d/m/Y), no el texto
                                 crudo de la celda: si Excel la reconoce como fecha nativa
                                 (cellDates), no hay ambigüedad de día/mes que mostrar. -->
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.fechaNormalizada ||
                                state.resultado.encabezado.fecha || '—' }}</span>
                            <span v-if="erroresDeCampo(state.resultado.erroresEncabezado, 'fecha')"
                                class="mt-1 flex items-start gap-1 text-[11px] leading-tight text-red-600">
                                <AlertCircle class="size-3 shrink-0 mt-0.5" />
                                {{ erroresDeCampo(state.resultado.erroresEncabezado, 'fecha') }}
                            </span>
                        </div>
                        <div
                            :class="{ 'rounded-md ring-1 ring-red-300 bg-red-50/60 -m-1 p-1': erroresDeCampo(state.resultado.erroresEncabezado, 'correoVendedor') }">
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Vendedor</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.vendedor || '—'
                            }}</span>
                            <span v-if="erroresDeCampo(state.resultado.erroresEncabezado, 'correoVendedor')"
                                class="mt-1 flex items-start gap-1 text-[11px] leading-tight text-red-600">
                                <AlertCircle class="size-3 shrink-0 mt-0.5" />
                                {{ erroresDeCampo(state.resultado.erroresEncabezado, 'correoVendedor') }}
                            </span>
                        </div>
                    </div>

                    <!-- Alertas globales -->
                    <div v-if="state.resultado.erroresGlobales.length > 0"
                        class="shrink-0 rounded-lg bg-red-50 p-4 text-red-900 border border-red-200">
                        <ul class="list-disc list-inside text-sm space-y-1">
                            <li v-for="(err, i) in state.resultado.erroresGlobales" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <!-- Tabla de partidas: ocupa todo el espacio restante, con scroll propio -->
                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead class="sticky top-0 z-10 bg-muted text-xs uppercase text-muted-foreground shadow-sm">
                                <tr>
                                    <th class="border-b px-3 py-2.5 font-medium w-24">No.</th>
                                    <th class="border-b px-3 py-2.5 font-medium">Descripción</th>
                                    <th class="border-b px-3 py-2.5 font-medium w-24">Unidad</th>
                                    <th class="border-b px-3 py-2.5 font-medium w-28 text-right">Cantidad</th>
                                    <th class="border-b px-3 py-2.5 font-medium w-32 text-right">P.U.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(partida, idx) in state.resultado.partidas" :key="idx" :class="[
                                    partida.esPadre ? 'sticky top-[37px] z-[5] bg-muted/60 font-semibold backdrop-blur-sm' : 'bg-background',
                                    partida.errores.length > 0 ? 'bg-red-50/50' : ''
                                ]">

                                    <!-- Celda No. -->
                                    <td class="px-3 py-2 border-r align-top relative group"
                                        :class="{ 'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'no') }">
                                        <div class="flex items-start gap-1">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'no')"
                                                class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span>{{ partida.no || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'no')"
                                            class="text-[11px] text-red-600 mt-1 leading-tight">
                                            {{ erroresDeCelda(partida.errores, 'no') }}
                                        </div>
                                    </td>

                                    <!-- Celda Descripción -->
                                    <td class="px-3 py-2 border-r align-top"
                                        :class="{ 'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'descripcion') }">
                                        <div class="flex items-start gap-1" :class="!partida.esPadre ? 'pl-4' : ''">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'descripcion')"
                                                class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span>{{ partida.descripcion || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'descripcion')"
                                            class="text-[11px] text-red-600 mt-1 leading-tight">
                                            {{ erroresDeCelda(partida.errores, 'descripcion') }}
                                        </div>
                                    </td>

                                    <!-- Celda Unidad -->
                                    <td class="px-3 py-2 border-r align-top text-center"
                                        :class="{ 'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'unidad'), 'opacity-40': partida.esPadre }">
                                        <div v-if="!partida.esPadre">{{ partida.unidad }}</div>
                                        <div v-if="erroresDeCelda(partida.errores, 'unidad')"
                                            class="text-[11px] text-red-600 mt-1 leading-tight text-left">
                                            {{ erroresDeCelda(partida.errores, 'unidad') }}
                                        </div>
                                    </td>

                                    <!-- Celda Cantidad -->
                                    <td class="px-3 py-2 border-r align-top text-right"
                                        :class="{ 'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'cantidad'), 'opacity-40': partida.esPadre }">
                                        <div class="flex items-start gap-1 justify-end">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'cantidad')"
                                                class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span v-if="!partida.esPadre">{{ partida.cantidad || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'cantidad')"
                                            class="text-[11px] text-red-600 mt-1 leading-tight text-right">
                                            {{ erroresDeCelda(partida.errores, 'cantidad') }}
                                        </div>
                                    </td>

                                    <!-- Celda P.U. -->
                                    <td class="px-3 py-2 align-top text-right"
                                        :class="{ 'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'precio_unitario'), 'opacity-40': partida.esPadre }">
                                        <div class="flex items-start gap-1 justify-end">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'precio_unitario')"
                                                class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span v-if="!partida.esPadre">{{ partida.precioUnitario || '(vacío)'
                                            }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'precio_unitario')"
                                            class="text-[11px] text-red-600 mt-1 leading-tight text-right">
                                            {{ erroresDeCelda(partida.errores, 'precio_unitario') }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <DialogFooter class="shrink-0 items-center gap-2 border-t px-6 py-4 sm:justify-between">
                <div class="flex-1 flex items-center gap-2">
                    <template v-if="state.resultado">
                        <div v-if="tieneErrores" class="flex items-center gap-2 text-red-600 font-medium">
                            <X class="size-5" />
                            <span>{{ totalErrores }} {{ totalErrores === 1 ? 'error detectado' : 'errores detectados'
                            }}.</span>
                            <span class="text-sm font-normal text-muted-foreground ml-2 hidden sm:inline">Corrige el
                                Excel y vuelve a subirlo.</span>
                        </div>
                        <div v-else class="flex items-center gap-2 text-emerald-600 font-medium">
                            <CheckCircle2 class="size-5" />
                            <span>El formato es válido.</span>
                        </div>
                    </template>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" @click="handleCancel">Cancelar</Button>
                    <Button :disabled="tieneErrores || state.cargando || !state.resultado" @click="handleConfirm">
                        Subir cotización
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
