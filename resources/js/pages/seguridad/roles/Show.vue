<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { index as rolesIndex } from '@/routes/seguridad/roles';

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
import { ChevronDown, ChevronRight } from '@lucide/vue';
import { reactive, ref } from 'vue';
import PageLayout from '@/components/PageLayout.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { permisos as permisosRole } from '@/actions/App/Http/Controllers/Seguridad/RoleController';

interface OperacionNodo {
    permisoOperacionId: number | null;
    clave: string;
    nombre: string;
}

interface PermisoNodo {
    id: number;
    nombre: string;
    endpoint: string | null;
    operaciones: OperacionNodo[];
    hijos: PermisoNodo[];
}

const props = defineProps<{
    role: { id: number; nombre: string; activo: boolean; usuarios_count: number };
    permisosArbol: PermisoNodo[];
    permisosAsignados: number[];
}>();

const concesiones = reactive<Set<number>>(new Set(props.permisosAsignados));
const nodosExpandidos = reactive<Set<number>>(new Set());
const procesando = ref(false);

function alternarExpandido(id: number): void {
    if (nodosExpandidos.has(id)) {
        nodosExpandidos.delete(id);
    } else {
        nodosExpandidos.add(id);
    }
}

function estaConcedida(operacion: OperacionNodo): boolean {
    return operacion.permisoOperacionId !== null && concesiones.has(operacion.permisoOperacionId);
}

function alternarOperacion(operacion: OperacionNodo): void {
    if (operacion.permisoOperacionId === null) return;

    if (concesiones.has(operacion.permisoOperacionId)) {
        concesiones.delete(operacion.permisoOperacionId);
    } else {
        concesiones.add(operacion.permisoOperacionId);
    }
}

function idsDelSubarbol(nodo: PermisoNodo): number[] {
    return [
        ...nodo.operaciones.map((o) => o.permisoOperacionId).filter((id): id is number => id !== null),
        ...nodo.hijos.flatMap(idsDelSubarbol),
    ];
}

function otorgarTodo(nodo: PermisoNodo): void {
    idsDelSubarbol(nodo).forEach((id) => concesiones.add(id));
}

function revocarTodo(nodo: PermisoNodo): void {
    idsDelSubarbol(nodo).forEach((id) => concesiones.delete(id));
}

function guardar(): void {
    procesando.value = true;
    router.put(
        permisosRole.url(props.role.id),
        { concesiones: Array.from(concesiones) },
        { preserveScroll: true, onFinish: () => (procesando.value = false) },
    );
}
</script>

<template>
    <Head :title="`Rol: ${role.nombre}`" />

    <PageLayout :title="role.nombre" :description="`${role.usuarios_count} usuarios con este rol`" endpoint="seguridad.roles">
        <template #actions>
            <Button :disabled="procesando" @click="guardar">Guardar permisos</Button>
        </template>

        <div class="space-y-2">
            <div v-for="nodo in permisosArbol" :key="nodo.id" class="rounded-xl border">
                <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                    <button
                        v-if="nodo.hijos.length"
                        type="button"
                        class="rounded p-0.5 hover:bg-accent"
                        @click="alternarExpandido(nodo.id)"
                    >
                        <ChevronDown v-if="nodosExpandidos.has(nodo.id)" class="size-4" />
                        <ChevronRight v-else class="size-4" />
                    </button>
                    <span v-else class="w-5" />

                    <span class="min-w-32 flex-1 font-medium">{{ nodo.nombre }}</span>

                    <label
                        v-for="operacion in nodo.operaciones"
                        :key="operacion.clave"
                        class="flex items-center gap-1.5 text-sm text-muted-foreground"
                    >
                        <Checkbox :model-value="estaConcedida(operacion)" @update:model-value="alternarOperacion(operacion)" />
                        {{ operacion.nombre }}
                    </label>

                    <div v-if="nodo.operaciones.length || nodo.hijos.length" class="flex gap-2">
                        <button type="button" class="text-xs text-muted-foreground underline hover:text-foreground" @click="otorgarTodo(nodo)">
                            Todo
                        </button>
                        <button type="button" class="text-xs text-muted-foreground underline hover:text-foreground" @click="revocarTodo(nodo)">
                            Ninguno
                        </button>
                    </div>
                </div>

                <div v-if="nodo.hijos.length && nodosExpandidos.has(nodo.id)" class="space-y-2 border-t bg-muted/20 p-3 pl-8">
                    <div v-for="hijo in nodo.hijos" :key="hijo.id" class="flex flex-wrap items-center gap-3 rounded-lg border bg-card px-4 py-2.5">
                        <span class="min-w-32 flex-1 text-sm font-medium">{{ hijo.nombre }}</span>
                        <label
                            v-for="operacion in hijo.operaciones"
                            :key="operacion.clave"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Checkbox :model-value="estaConcedida(operacion)" @update:model-value="alternarOperacion(operacion)" />
                            {{ operacion.nombre }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
