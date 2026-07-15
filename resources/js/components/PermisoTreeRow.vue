<!-- resources/js/components/PermisoTreeRow.vue -->
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
    cambiar: [permisoId: number, bit: number, activo: boolean];
    quitar: [permisoId: number];
    modulo: [ids: number[], activo: boolean];
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

const TODAS = 15;

function tiene(bit: number) {
    return (efectivo.value & bit) === bit;
}

// Ids for this node plus every descendant, used for whole-module selection.
function subarbolIds(nodo: PermisoNodo): number[] {
    return [nodo.id, ...nodo.hijos.flatMap(subarbolIds)];
}
const idsSubarbol = computed(() => subarbolIds(props.nodo));

const estadoModulo = computed<boolean | 'indeterminate'>(() => {
    const total = idsSubarbol.value.length;
    const completos = idsSubarbol.value.filter((id) => (props.valores[id] ?? 0) === TODAS).length;
    if (completos === 0) return false;
    if (completos === total) return true;
    return 'indeterminate';
});
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
                    @update:model-value="(v) => emit('modulo', idsSubarbol, v === true)"
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
                <Checkbox :model-value="tiene(accion.bit)" @update:model-value="(v) => emit('cambiar', nodo.id, accion.bit, v === true)" />
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
                @cambiar="(...args) => emit('cambiar', ...args)"
                @quitar="(id) => emit('quitar', id)"
                @modulo="(ids, activo) => emit('modulo', ids, activo)"
            />
        </div>
    </div>
</template>
