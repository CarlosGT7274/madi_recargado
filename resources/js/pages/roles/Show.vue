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
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PermisoTreeRow from '@/components/PermisoTreeRow.vue';
import { Button } from '@/components/ui/button';
import { permisos as permisosRole } from '@/actions/App/Http/Controllers/RoleController';
import type { PermisoNodo } from '@/types/roles';

const props = defineProps<{
    role: { id: number; nombre: string; activo: boolean; usuarios_count: number };
    permisosArbol: PermisoNodo[];
    permisosAsignados: Record<number, number>;
}>();

const valores = reactive<Record<number, number>>({ ...props.permisosAsignados });
const procesando = ref(false);

function cambiar(permisoId: number, bit: number, activo: boolean) {
    const actual = valores[permisoId] ?? 0;
    valores[permisoId] = activo ? actual | bit : actual & ~bit;
}

function quitar(permisoId: number) {
    delete valores[permisoId];
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
        <div class="flex items-center justify-between">
            <Heading :title="role.nombre" :description="`${role.usuarios_count} usuarios con este rol`" />
            <Button :disabled="procesando" @click="guardar">Guardar permisos</Button>
        </div>
        <div class="overflow-hidden rounded-xl border">
            <div class="grid grid-cols-[1fr_repeat(4,80px)_90px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                <span>Módulo</span><span class="text-center">Ver</span><span class="text-center">Crear</span>
                <span class="text-center">Editar</span><span class="text-center">Eliminar</span><span />
            </div>
            <PermisoTreeRow v-for="nodo in permisosArbol" :key="nodo.id" :nodo="nodo" :valores="valores" @cambiar="cambiar" @quitar="quitar" />
        </div>
    </div>
</template>
