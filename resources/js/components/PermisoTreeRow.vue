<script setup lang="ts">
import { computed, ref } from 'vue';
import { Check, ChevronDown, ChevronRight, Minus } from '@lucide/vue';
import { Checkbox } from '@/components/ui/checkbox';
import type { Operacion, PermisoNodo } from '@/types/roles';

const props = withDefaults(
    defineProps<{
        nodo: PermisoNodo;
        valores: Record<number, number>;
        operaciones: Operacion[];
        profundidad?: number;
    }>(),
    { profundidad: 0 },
);

const emit = defineEmits<{
    cambiar: [ids: number[], bit: number, activo: boolean];
    modulo: [ids: number[], activo: boolean];
}>();

const abierto = ref(true);
const tieneHijos = computed(() => props.nodo.hijos.length > 0);

type Estado = boolean | 'indeterminate';

function subarbol(nodo: PermisoNodo): PermisoNodo[] {
    return [nodo, ...nodo.hijos.flatMap(subarbol)];
}

function idsAplicables(bit: number): number[] {
    return subarbol(props.nodo)
        .filter((n) => (n.operacionesAplicables & bit) === bit)
        .map((n) => n.id);
}

function aplica(bit: number): boolean {
    return (props.nodo.operacionesAplicables & bit) === bit;
}

function estadoBit(bit: number): Estado {
    const ids = idsAplicables(bit);
    if (!ids.length) return false;
    const activos = ids.filter((id) => ((props.valores[id] ?? 0) & bit) === bit).length;
    if (activos === 0) return false;
    if (activos === ids.length) return true;
    return 'indeterminate';
}

function alternarBit(bit: number) {
    const activar = estadoBit(bit) !== true;
    emit('cambiar', idsAplicables(bit), bit, activar);
}

function paresAplicables(): { id: number; bit: number }[] {
    return subarbol(props.nodo).flatMap((n) =>
        props.operaciones.filter((op) => (n.operacionesAplicables & op.bit) === op.bit).map((op) => ({ id: n.id, bit: op.bit })),
    );
}

const estadoTodo = computed<Estado>(() => {
    const pares = paresAplicables();
    if (!pares.length) return false;
    const activos = pares.filter((p) => ((props.valores[p.id] ?? 0) & p.bit) === p.bit).length;
    if (activos === 0) return false;
    if (activos === pares.length) return true;
    return 'indeterminate';
});

function alternarTodo() {
    const activar = estadoTodo.value !== true;
    const ids = Array.from(new Set(paresAplicables().map((p) => p.id)));
    emit('modulo', ids, activar);
}
</script>

<template>
    <div class="border-t first:border-t-0">
        <div
            class="grid items-center gap-2 px-4 py-2.5 text-sm hover:bg-accent/40"
            :style="{ gridTemplateColumns: `1fr repeat(${operaciones.length}, 64px) 56px` }"
        >
            <div class="flex min-w-0 items-stretch">
                <!-- Rieles de indentación: un guía por cada nivel de profundidad -->
                <span
                    v-for="nivel in profundidad"
                    :key="nivel"
                    aria-hidden="true"
                    class="ml-2 w-4 shrink-0 self-stretch border-l border-border/60"
                />
                <div class="flex min-w-0 items-center gap-1.5" :class="profundidad > 0 ? 'pl-1.5' : ''">
                    <button
                        v-if="tieneHijos"
                        type="button"
                        class="rounded p-0.5 text-muted-foreground hover:bg-accent"
                        :aria-expanded="abierto"
                        :aria-label="`${abierto ? 'Contraer' : 'Expandir'} ${nodo.nombre}`"
                        @click="abierto = !abierto"
                    >
                        <ChevronDown v-if="abierto" class="size-4" />
                        <ChevronRight v-else class="size-4" />
                    </button>
                    <span v-else class="inline-block w-5 shrink-0" />
                    <span
                        class="truncate"
                        :class="profundidad === 0 ? 'font-semibold' : tieneHijos ? 'font-medium' : 'font-normal text-muted-foreground'"
                    >
                        {{ nodo.nombre }}
                    </span>
                </div>
            </div>

            <div v-for="op in operaciones" :key="op.id" class="flex justify-center">
                <Checkbox
                    v-if="aplica(op.bit)"
                    :model-value="estadoBit(op.bit)"
                    :aria-label="`${op.nombre} en ${nodo.nombre}`"
                    @update:model-value="alternarBit(op.bit)"
                >
                    <template #default="{ state }">
                        <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                        <Check v-else class="size-3.5" />
                    </template>
                </Checkbox>
                <span
                    v-else
                    class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground/70"
                    title="No aplica"
                >
                    N/A
                </span>
            </div>

            <div class="flex justify-center">
                <Checkbox :model-value="estadoTodo" aria-label="Todas las operaciones" @update:model-value="alternarTodo">
                    <template #default="{ state }">
                        <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                        <Check v-else class="size-3.5" />
                    </template>
                </Checkbox>
            </div>
        </div>

        <div v-if="tieneHijos && abierto" class="bg-muted/20">
            <PermisoTreeRow
                v-for="hijo in nodo.hijos"
                :key="hijo.id"
                :nodo="hijo"
                :valores="valores"
                :operaciones="operaciones"
                :profundidad="profundidad + 1"
                @cambiar="(ids, bit, activo) => emit('cambiar', ids, bit, activo)"
                @modulo="(ids, activo) => emit('modulo', ids, activo)"
            />
        </div>
    </div>
</template>
