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
import { BadgeCheck, Check, Circle, Eye, Minus, Pencil, Plus, Telescope, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { permisos as permisosRole } from '@/actions/App/Http/Controllers/Seguridad/RoleController';
import PageLayout from '@/components/PageLayout.vue';
import PermisoTreeRow from '@/components/PermisoTreeRow.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import type { Operacion, PermisoNodo } from '@/types/roles';
import { toast } from 'vue-sonner';

const props = defineProps<{
    role: { id: number; nombre: string; activo: boolean; usuarios_count: number };
    permisosArbol: PermisoNodo[];
    permisosAsignados: Record<number, number>;
    operaciones: Operacion[];
}>();

type EstadoCheck = boolean | 'indeterminate';

/**
 * El backend (Role::permisosPara) hereda: si un permiso no tiene fila
 * propia en roles_permisos, trepa al padre y usa su máscara. `mapaPermisos`
 * solo devuelve las filas EXPLÍCITAS, así que al editar un rol existente los
 * hijos que heredan del padre llegarían vacíos y el árbol se vería
 * inconsistente (padre marcado, hijos sin marcar). Resolvemos la herencia
 * aquí, de arriba hacia abajo, para que cada nodo arranque con su estado
 * efectivo real; enmascaramos por operacionesAplicables para no encender
 * bits que ese objeto no soporta.
 */
function construirValoresIniciales(): Record<number, number> {
    const asignados = props.permisosAsignados as Record<number, number>;
    const resultado: Record<number, number> = {};

    const recorrer = (nodos: PermisoNodo[], heredado: number) => {
        for (const nodo of nodos) {
            const explicito = asignados[nodo.id];
            const base = explicito !== undefined ? explicito : heredado;
            resultado[nodo.id] = base & nodo.operacionesAplicables;
            recorrer(nodo.hijos, base);
        }
    };

    recorrer(props.permisosArbol, 0);

    return resultado;
}

const valores = reactive<Record<number, number>>(construirValoresIniciales());
const procesando = ref(false);

const iconos: Record<string, typeof Eye> = {
    ver: Eye,
    crear: Plus,
    editar: Pencil,
    eliminar: Trash2,
    aprobar: BadgeCheck,
    supervisar: Telescope,
};

function icono(clave: string) {
    return iconos[clave] ?? Circle;
}

function subarbol(nodo: PermisoNodo): PermisoNodo[] {
    return [nodo, ...nodo.hijos.flatMap(subarbol)];
}

const nodosTodos = computed<PermisoNodo[]>(() => props.permisosArbol.flatMap(subarbol));

function idsAplicables(bit: number): number[] {
    return nodosTodos.value.filter((n) => (n.operacionesAplicables & bit) === bit).map((n) => n.id);
}

function paresAplicablesTodos(): { id: number; bit: number }[] {
    return nodosTodos.value.flatMap((n) =>
        props.operaciones.filter((op) => (n.operacionesAplicables & op.bit) === op.bit).map((op) => ({ id: n.id, bit: op.bit })),
    );
}

const calcularEstado = (activos: number, total: number): EstadoCheck => {
    if (total === 0 || activos === 0) return false;
    if (activos === total) return true;
    return 'indeterminate';
};

const estadoGlobal = computed<EstadoCheck>(() => {
    const pares = paresAplicablesTodos();
    const activos = pares.filter((p) => ((valores[p.id] ?? 0) & p.bit) === p.bit).length;
    return calcularEstado(activos, pares.length);
});

/**
 * Un permiso "desmarcado" debe guardar 0 EXPLÍCITO, no borrarse. Borrar
 * la fila es indistinguible de "nunca se tocó" para Role::permisosPara(),
 * que en ese caso trepa al padre y hereda sus bits — eso convertía un
 * "niego este permiso" en "vuelve a heredar del padre". Guardar 0
 * explícito bloquea esa herencia correctamente.
 */
function aplicarATodos(activo: boolean) {
    if (!activo) {
        nodosTodos.value.forEach((n) => {
            valores[n.id] = 0;
        });
        return;
    }

    const porId: Record<number, number> = {};
    paresAplicablesTodos().forEach((p) => {
        porId[p.id] = (porId[p.id] ?? 0) | p.bit;
    });
    Object.entries(porId).forEach(([id, mask]) => {
        valores[Number(id)] = mask;
    });
}

const seleccionarTodo = () => aplicarATodos(true);
const limpiarTodo = () => aplicarATodos(false);

function estadoColumna(bit: number): EstadoCheck {
    const ids = idsAplicables(bit);
    if (!ids.length) return false;
    const activos = ids.filter((id) => ((valores[id] ?? 0) & bit) === bit).length;
    return calcularEstado(activos, ids.length);
}

function alternarColumna(bit: number, activo: boolean) {
    idsAplicables(bit).forEach((id) => {
        const actual = valores[id] ?? 0;
        valores[id] = activo ? actual | bit : actual & ~bit;
    });
}

function cambiar(ids: number[], bit: number, activo: boolean) {
    ids.forEach((id) => {
        const actual = valores[id] ?? 0;
        valores[id] = activo ? actual | bit : actual & ~bit;
    });
}

function alternarModulo(ids: number[], activo: boolean) {
    ids.forEach((id) => {
        if (!activo) {
            valores[id] = 0;
            return;
        }
        const nodo = nodosTodos.value.find((n) => n.id === id);
        valores[id] = nodo ? nodo.operacionesAplicables : 0;
    });
}

const guardar = () => {
    procesando.value = true;
    router.put(permisosRole.url(props.role.id), { permisos: valores }, {
        preserveScroll: true,
        onError: () => {
            toast.error('No se pudieron guardar los permisos. Verifica tus propios permisos e intenta de nuevo.');
        },
        onFinish: () => (procesando.value = false),
    });
};
</script>

<template>

    <Head :title="`Rol: ${role.nombre}`" />

    <PageLayout :title="role.nombre" :description="`${role.usuarios_count} usuarios con este rol`"
        endpoint="seguridad.roles">
        <template #actions>
            <Button variant="outline" size="sm" @click="seleccionarTodo">Seleccionar todo</Button>
            <Button variant="outline" size="sm" @click="limpiarTodo">Limpiar todo</Button>
            <Button :disabled="procesando" @click="guardar">Guardar permisos</Button>
        </template>

        <div class="mb-4 flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm">
            <span
                class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                {{ role.nombre.charAt(0) }}
            </span>
            <div class="flex-1">
                <p class="font-semibold">{{ role.nombre }}</p>
                <p class="text-xs text-muted-foreground">{{ role.usuarios_count }} usuarios asignados</p>
            </div>
            <span :class="role.activo ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'"
                class="text-xs font-medium">
                {{ role.activo ? 'Activo' : 'Inactivo' }}
            </span>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="grid items-end gap-2 border-b bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground"
                :style="{ gridTemplateColumns: `1fr repeat(${operaciones.length}, 64px) 56px` }">
                <div class="flex items-center gap-2 pb-1">
                    <Checkbox :model-value="estadoGlobal" aria-label="Seleccionar todos los permisos"
                        @update:model-value="(v) => (v === true ? seleccionarTodo() : limpiarTodo())">
                        <template #default="{ state }">
                            <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                            <Check v-else class="size-3.5" />
                        </template>
                    </Checkbox>
                    <span>Módulo</span>
                </div>

                <div v-for="op in operaciones" :key="op.id" class="flex flex-col items-center gap-1">
                    <component :is="icono(op.clave)" class="size-3.5" :class="op.basica ? '' : 'text-primary'" />
                    <span :class="op.basica ? '' : 'text-primary'">{{ op.nombre }}</span>
                    <Checkbox :model-value="estadoColumna(op.bit)" :aria-label="`Alternar columna ${op.nombre}`"
                        @update:model-value="(v) => alternarColumna(op.bit, v === true)">
                        <template #default="{ state }">
                            <Minus v-if="state === 'indeterminate'" class="size-3.5" />
                            <Check v-else class="size-3.5" />
                        </template>
                    </Checkbox>
                </div>

                <span class="pb-1 text-center">Todo</span>
            </div>

            <PermisoTreeRow v-for="nodo in permisosArbol" :key="nodo.id" :nodo="nodo" :valores="valores"
                :operaciones="operaciones" @cambiar="cambiar" @modulo="alternarModulo" />
        </div>

        <p class="mt-3 text-xs text-muted-foreground">
            <span class="rounded-full bg-muted px-1.5 py-0.5 font-medium">N/A</span>
            No aplica: esta operación no existe para ese objeto.
        </p>
    </PageLayout>
</template>
