<script setup lang="ts">
import { computed, watch } from 'vue';
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
import { useExcelCotizacionValidator } from '@/composables/useExcelCotizacionValidator';

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
    if (props.archivo && !tieneErrores.value) {
        emit('confirm', props.archivo);
        emit('update:open', false);
    }
}

function handleCancel() {
    emit('cancel');
    emit('update:open', false);
}

function erroresDeCelda(errores: any[], columna: string): string {
    const err = errores.find(e => e.columna === columna);
    return err ? err.mensaje : '';
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="max-w-4xl max-h-[90vh] flex flex-col">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <FileSpreadsheet class="size-5 text-primary" />
                    Validación del Excel
                </DialogTitle>
                <DialogDescription>
                    Revisión de formato y estructura antes de subir la cotización.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-hidden flex flex-col gap-4 py-4">
                <div v-if="state.cargando" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
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
                    <!-- Resumen del encabezado -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm bg-muted/50 p-3 rounded-lg border">
                        <div>
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Obra</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.obra || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Cliente</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.cliente || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Fecha</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.fecha || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-xs uppercase font-medium">Vendedor</span>
                            <span class="font-medium truncate block">{{ state.resultado.encabezado.vendedor || '—' }}</span>
                        </div>
                    </div>

                    <!-- Alertas globales -->
                    <div v-if="state.resultado.erroresGlobales.length > 0" class="rounded-lg bg-red-50 p-4 text-red-900 border border-red-200">
                        <ul class="list-disc list-inside text-sm space-y-1">
                            <li v-for="(err, i) in state.resultado.erroresGlobales" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <!-- Tabla de partidas con scroll -->
                    <div class="flex-1 overflow-auto border rounded-lg">
                        <table class="w-full text-sm text-left relative border-collapse">
                            <thead class="text-xs text-muted-foreground uppercase bg-muted sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-3 py-2 border-b font-medium w-24">No.</th>
                                    <th class="px-3 py-2 border-b font-medium">Descripción</th>
                                    <th class="px-3 py-2 border-b font-medium w-24">Unidad</th>
                                    <th class="px-3 py-2 border-b font-medium w-28 text-right">Cantidad</th>
                                    <th class="px-3 py-2 border-b font-medium w-32 text-right">P.U.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(partida, idx) in state.resultado.partidas" :key="idx"
                                    :class="[
                                        partida.esPadre ? 'bg-muted/20 font-medium' : 'bg-background',
                                        partida.errores.length > 0 ? 'bg-red-50/50' : ''
                                    ]">
                                    
                                    <!-- Celda No. -->
                                    <td class="px-3 py-2 border-r align-top relative group" :class="{'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'no')}">
                                        <div class="flex items-start gap-1">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'no')" class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span>{{ partida.no || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'no')" class="text-[11px] text-red-600 mt-1 leading-tight">
                                            {{ erroresDeCelda(partida.errores, 'no') }}
                                        </div>
                                    </td>

                                    <!-- Celda Descripción -->
                                    <td class="px-3 py-2 border-r align-top" :class="{'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'descripcion')}">
                                        <div class="flex items-start gap-1">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'descripcion')" class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span>{{ partida.descripcion || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'descripcion')" class="text-[11px] text-red-600 mt-1 leading-tight">
                                            {{ erroresDeCelda(partida.errores, 'descripcion') }}
                                        </div>
                                    </td>

                                    <!-- Celda Unidad -->
                                    <td class="px-3 py-2 border-r align-top text-center" :class="{'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'unidad'), 'opacity-40': partida.esPadre}">
                                        <div v-if="!partida.esPadre">{{ partida.unidad }}</div>
                                        <div v-if="erroresDeCelda(partida.errores, 'unidad')" class="text-[11px] text-red-600 mt-1 leading-tight text-left">
                                            {{ erroresDeCelda(partida.errores, 'unidad') }}
                                        </div>
                                    </td>

                                    <!-- Celda Cantidad -->
                                    <td class="px-3 py-2 border-r align-top text-right" :class="{'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'cantidad'), 'opacity-40': partida.esPadre}">
                                        <div class="flex items-start gap-1 justify-end">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'cantidad')" class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span v-if="!partida.esPadre">{{ partida.cantidad || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'cantidad')" class="text-[11px] text-red-600 mt-1 leading-tight text-right">
                                            {{ erroresDeCelda(partida.errores, 'cantidad') }}
                                        </div>
                                    </td>

                                    <!-- Celda P.U. -->
                                    <td class="px-3 py-2 align-top text-right" :class="{'bg-red-100/50 text-red-900': erroresDeCelda(partida.errores, 'precio_unitario'), 'opacity-40': partida.esPadre}">
                                        <div class="flex items-start gap-1 justify-end">
                                            <AlertCircle v-if="erroresDeCelda(partida.errores, 'precio_unitario')" class="size-4 text-red-500 shrink-0 mt-0.5" />
                                            <span v-if="!partida.esPadre">{{ partida.precioUnitario || '(vacío)' }}</span>
                                        </div>
                                        <div v-if="erroresDeCelda(partida.errores, 'precio_unitario')" class="text-[11px] text-red-600 mt-1 leading-tight text-right">
                                            {{ erroresDeCelda(partida.errores, 'precio_unitario') }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <DialogFooter class="sm:justify-between items-center border-t pt-4">
                <div class="flex-1 flex items-center gap-2">
                    <template v-if="state.resultado">
                        <div v-if="tieneErrores" class="flex items-center gap-2 text-red-600 font-medium">
                            <X class="size-5" />
                            <span>{{ totalErrores }} {{ totalErrores === 1 ? 'error detectado' : 'errores detectados' }}.</span>
                            <span class="text-sm font-normal text-muted-foreground ml-2 hidden sm:inline">Por favor corrige el Excel y vuelve a subirlo.</span>
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
