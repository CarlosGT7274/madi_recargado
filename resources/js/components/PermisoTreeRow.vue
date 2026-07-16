<script setup lang="ts">
import { computed, ref } from 'vue';
import { Check, ChevronDown, ChevronRight, Minus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import type { PermisoNodo } from '@/types/roles';

const props = defineProps<{
    nodo: PermisoNodo;
    valores: Record<number, number>;
    heredado?: number;
    depth?: number;
}>();

const emit = defineEmits<{
    cambiar: [ids: number[], bit: number, activo: boolean];
    quitar: [permisoId: number];
}>();

const abierto = ref(true);
const depth = props.depth ?? 0;
const propio = computed(() => props.valores[props.nodo.id]);
const efectivo = computed(() => propio.value ?? props.heredado ?? 0);

const ACCIONES = [
    { bit: 1, label: 'Ver' },
    { bit: 2, label: 'Crear' },
    { bit: 4, label: 'Editar' },
    { bit: 8, label: 'Eliminar' },
] as const;

function tiene(bit: number) {
    return (efectivo.value & bit) === bit;
}

function idsSubarbol(nodo: PermisoNodo): number[] {
    return [nodo.id, ...nodo.hijos.flatMap(idsSubarbol)];
}
const propiosIds = computed(() => idsSubarbol(props.nodo));

/**
 * Estado agregado de un bit para todo el subárbol de `nodo`, usado para
 * reflejar en un padre lo que ocurre en sus hijos aunque el padre no
 * tenga asignación propia explícita.
 */
function estadoBit(nodo: PermisoNodo, bit: number, heredadoBit: boolean): 'on' | 'off' | 'mixed' {
    const valor = props.valores[nodo.id];
    const propioBit = valor !== undefined ? (valor & bit) === bit : undefined;

    if (nodo.hijos.length === 0) {
        return (propioBit ?? heredadoBit) ? 'on' : 'off';
    }

    if (propioBit !== undefined) {
        return propioBit ? 'on' : 'off';
    }

    const estados = nodo.hijos.map((hijo) => estadoBit(hijo, bit, heredadoBit));
    if (estados.every((e) => e === 'on')) return 'on';
    if (estados.every((e) => e === 'off')) return 'off';
    return 'mixed';
}

function estadoColumna(bit: number): boolean | 'indeterminate' {
    if (!props.nodo.hijos.length) {
        return tiene(bit);
    }
    const heredadoBit = ((props.heredado ?? 0) & bit) === bit;
    const estado = estadoBit(props.nodo, bit, heredadoBit);
    return estado === 'mixed' ? 'indeterminate' : estado === 'on';
}

function alternarBit(bit: number, activo: boolean) {
    emit('cambiar', propiosIds.value, bit, activo);
}

const estadoModulo = computed<boolean | 'indeterminate'>(() => {
    const estados = ACCIONES.map(({ bit }) => estadoColumna(bit));
    if (estados.every((e) => e === true)) return true;
    if (estados.every((e) => e === false)) return false;
    return 'indeterminate';
});

function alternarModulo(activo: boolean) {
    ACCIONES.forEach(({ bit }) => alternarBit(bit, activo));
}
</script>

<template>
    <div>
        <div
            class="grid grid-cols-[1fr_repeat(4,80px)_90px] items-center gap-2 border-t px-4 py-2"
            :style="{ paddingLeft: `${1 + depth * 1.5}rem` }"
        >
            <div class="flex items-center gap-2">
                <button v-if="nodo.hijos.length" type="button" class="text-muted-foreground" @click="abierto = !abierto">
                    <ChevronDown v-if="abierto" class="size-4" />
                    <ChevronRight v-else class="size-4" />
                </button>
                <span v-else class="inline-block w-4" />

                <Checkbox
                    v-if="nodo.hijos.length"
                    :model-value="estadoModulo"
                    :aria-label="`Seleccionar todo el módulo ${nodo.nombre}`"
                    @update:model-value="(v) => alternarModulo(v === true)"
                >
                    <template #default="{ state }">
                        <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                        <Check v-else class="size-3.5" />
                    </template>
                </Checkbox>

                <div>
                    <p class="text-sm font-medium">{{ nodo.nombre }}</p>
                    <p v-if="nodo.endpoint" class="text-xs text-muted-foreground">/{{ nodo.endpoint }}</p>
                </div>
            </div>

            <div v-for="accion in ACCIONES" :key="accion.bit" class="flex justify-center">
                <Checkbox
                    :model-value="estadoColumna(accion.bit)"
                    @update:model-value="(v) => alternarBit(accion.bit, v === true)"
                >
                    <template v-if="nodo.hijos.length" #default="{ state }">
                        <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                        <Check v-else class="size-3.5" />
                    </template>
                </Checkbox>
            </div>

            <div class="flex justify-end">
                <Button v-if="propio !== undefined" variant="outline" size="sm" @click="emit('quitar', nodo.id)">Quitar</Button>
            </div>
        </div>

        <div v-if="abierto">
            <PermisoTreeRow
                v-for="hijo in nodo.hijos"
                :key="hijo.id"
                :nodo="hijo"
                :valores="valores"
                :heredado="efectivo"
                :depth="depth + 1"
                @cambiar="(ids, bit, activo) => emit('cambiar', ids, bit, activo)"
                @quitar="(id) => emit('quitar', id)"
            />
        </div>
    </div>
</template>
