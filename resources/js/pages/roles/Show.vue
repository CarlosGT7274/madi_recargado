<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { index as rolesIndex } from '@/routes/roles';

export default {
    layout: () => ({
        breadcrumbs: [
            { title: 'Roles', href: rolesIndex() },
            { title: (usePage().props.role as { nombre: string })?.nombre ?? '', href: '' },
        ],
    }),
};
</script>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Minus } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PermisoTreeRow from '@/components/PermisoTreeRow.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { permisos as permisosRole } from '@/actions/App/Http/Controllers/RoleController';
import type { PermisoNodo } from '@/types/roles';

const props = defineProps<{
    role: { id: number; nombre: string; activo: boolean; usuarios_count: number };
    permisosArbol: PermisoNodo[];
    permisosAsignados: Record<number, number>;
}>();

const ACCIONES = [
    { bit: 1, label: 'Ver' },
    { bit: 2, label: 'Crear' },
    { bit: 4, label: 'Editar' },
    { bit: 8, label: 'Eliminar' },
] as const;

const TODAS = 15;

const valores = reactive<Record<number, number>>({ ...props.permisosAsignados });
const procesando = ref(false);

// Flatten the permission tree once so bulk actions can reach every node.
function aplanar(nodos: PermisoNodo[]): PermisoNodo[] {
    return nodos.flatMap((nodo) => [nodo, ...aplanar(nodo.hijos)]);
}
const todosLosNodos = computed(() => aplanar(props.permisosArbol));
const todosLosIds = computed(() => todosLosNodos.value.map((nodo) => nodo.id));

type EstadoCheck = boolean | 'indeterminate';

function estadoDesde(coincidencias: number, total: number): EstadoCheck {
    if (total === 0 || coincidencias === 0) return false;
    if (coincidencias === total) return true;
    return 'indeterminate';
}

function cambiar(permisoId: number, bit: number, activo: boolean) {
    const actual = valores[permisoId] ?? 0;
    valores[permisoId] = activo ? actual | bit : actual & ~bit;
}

function quitar(permisoId: number) {
    delete valores[permisoId];
}

function fijarBit(ids: number[], bit: number, activo: boolean) {
    for (const id of ids) {
        const actual = valores[id] ?? 0;
        const nuevo = activo ? actual | bit : actual & ~bit;
        if (nuevo === 0) delete valores[id];
        else valores[id] = nuevo;
    }
}

// Per-column header toggle across the whole tree.
function estadoColumna(bit: number): EstadoCheck {
    const con = todosLosIds.value.filter((id) => ((valores[id] ?? 0) & bit) === bit).length;
    return estadoDesde(con, todosLosIds.value.length);
}
function alternarColumna(bit: number, activo: boolean) {
    fijarBit(todosLosIds.value, bit, activo);
}

// Whole-module bulk selection emitted by a tree row.
function alternarModulo(ids: number[], activo: boolean) {
    if (activo) {
        for (const id of ids) valores[id] = TODAS;
    } else {
        for (const id of ids) delete valores[id];
    }
}

// Global select-all / clear-all.
const estadoGlobal = computed<EstadoCheck>(() => {
    const con = todosLosIds.value.filter((id) => (valores[id] ?? 0) === TODAS).length;
    return estadoDesde(con, todosLosIds.value.length);
});
function seleccionarTodo() {
    for (const id of todosLosIds.value) valores[id] = TODAS;
}
function limpiarTodo() {
    for (const id of Object.keys(valores)) delete valores[Number(id)];
}

function guardar() {
    procesando.value = true;
    router.put(permisosRole.url(props.role.id), { permisos: valores }, {
        preserveScroll: true,
        onFinish: () => (procesando.value = false),
    });
}
</script>

<template>
    <Head :title="`Rol: ${role.nombre}`" />
    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Heading :title="role.nombre" :description="`${role.usuarios_count} usuarios con este rol`" />
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="seleccionarTodo">Seleccionar todo</Button>
                <Button variant="outline" size="sm" @click="limpiarTodo">Limpiar todo</Button>
                <Button :disabled="procesando" @click="guardar">Guardar permisos</Button>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border">
            <div class="grid grid-cols-[1fr_repeat(4,80px)_90px] items-end gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                <div class="flex items-center gap-2">
                    <Checkbox
                        :model-value="estadoGlobal"
                        aria-label="Seleccionar todos los permisos"
                        @update:model-value="(v) => (v === true ? seleccionarTodo() : limpiarTodo())"
                    >
                        <template #default="{ state }">
                            <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                            <Check v-else class="size-3.5" />
                        </template>
                    </Checkbox>
                    <span>Módulo</span>
                </div>
                <div v-for="accion in ACCIONES" :key="accion.bit" class="flex flex-col items-center gap-1">
                    <span>{{ accion.label }}</span>
                    <Checkbox
                        :model-value="estadoColumna(accion.bit)"
                        :aria-label="`Alternar columna ${accion.label}`"
                        @update:model-value="(v) => alternarColumna(accion.bit, v === true)"
                    >
                        <template #default="{ state }">
                            <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                            <Check v-else class="size-3.5" />
                        </template>
                    </Checkbox>
                </div>
                <span />
            </div>
            <PermisoTreeRow
                v-for="nodo in permisosArbol"
                :key="nodo.id"
                :nodo="nodo"
                :valores="valores"
                @cambiar="cambiar"
                @quitar="quitar"
                @modulo="alternarModulo"
            />
        </div>
    </div>
</template>
