<!-- resources/js/components/PermisoTreeRow.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue';
import { ChevronDown, ChevronRight } from '@lucide/vue';
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
                <div>
                    <p class="text-sm font-medium">{{ nodo.nombre }}</p>
                    <p v-if="nodo.endpoint" class="text-xs text-muted-foreground">/{{ nodo.endpoint }}</p>
                </div>
            </div>

            <div v-for="accion in ACCIONES" :key="accion.bit" class="flex justify-center">
                <Checkbox :checked="tiene(accion.bit)" @update:checked="(v) => emit('cambiar', nodo.id, accion.bit, v === true)" />
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
            />
        </div>
    </div>
</template>
