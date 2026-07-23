<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    destroy as destroyRole,
    store as storeRole,
    update as updateRole,
} from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as rolesIndex, show as rolesShow } from '@/routes/seguridad/roles';
import type { RoleResumen } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Roles', href: rolesIndex() }],
    },
});

const props = defineProps<{
    roles: RoleResumen[];
}>();

const rolEnEdicion = ref<RoleResumen | null>(null);
const dialogRolAbierto = ref(false);
const rolForm = useForm({
    nombre: '',
    activo: true,
});

function abrirCrear() {
    rolEnEdicion.value = null;
    rolForm.reset();
    rolForm.clearErrors();
    dialogRolAbierto.value = true;
}

function abrirEditar(role: RoleResumen) {
    rolEnEdicion.value = role;
    rolForm.clearErrors();
    rolForm.nombre = role.nombre;
    rolForm.activo = role.activo;
    dialogRolAbierto.value = true;
}

function guardarRol() {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => {
            dialogRolAbierto.value = false;
        },
    };
    if (rolEnEdicion.value) {
        rolForm.put(updateRole.url(rolEnEdicion.value.id), opciones);
        return;
    }
    rolForm.post(storeRole.url(), opciones);
}

const rolAEliminar = ref<RoleResumen | null>(null);
function confirmarEliminar() {
    if (!rolAEliminar.value) {
        return;
    }
    router.delete(destroyRole.url(rolAEliminar.value.id), {
        preserveScroll: true,
        onFinish: () => {
            rolAEliminar.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />
    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Roles" description="Administra los roles del sistema y sus permisos" />
            <Dialog v-model:open="dialogRolAbierto">
                <DialogTrigger as-child>
                    <Button @click="abrirCrear">Nuevo rol</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ rolEnEdicion ? 'Editar rol' : 'Nuevo rol' }}</DialogTitle>
                        <DialogDescription>
                            Define el nombre del rol y si estará disponible para asignarse a usuarios.
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="guardarRol">
                        <div class="grid gap-2">
                            <Label for="nombre">Nombre</Label>
                            <Input id="nombre" v-model="rolForm.nombre" required autofocus />
                            <InputError :message="rolForm.errors.nombre" />
                        </div>
                        <Label class="flex w-fit items-center gap-2">
                            <Checkbox v-model:checked="rolForm.activo" />
                            <span>Activo</span>
                        </Label>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">Cancelar</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="rolForm.processing">Guardar</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Nombre</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                        <th class="px-4 py-2 font-medium">Usuarios</th>
                        <th class="px-4 py-2 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in roles" :key="role.id" class="border-t">
                        <td class="px-4 py-2 font-medium">{{ role.nombre }}</td>
                        <td class="px-4 py-2">{{ role.activo ? 'Activo' : 'Inactivo' }}</td>
                        <td class="px-4 py-2">{{ role.usuarios_count }}</td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end gap-2">
                                <Link :href="rolesShow(role.id)">
                                    <Button variant="outline" size="sm">Permisos</Button>
                                </Link>
                                <Button variant="outline" size="sm" @click="abrirEditar(role)">Editar</Button>
                                <Button variant="destructive" size="sm" @click="rolAEliminar = role">Eliminar</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="roles.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                            Aún no hay roles registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <Dialog :open="!!rolAEliminar" @update:open="(open) => { if (!open) rolAEliminar = null; }">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Eliminar rol?</DialogTitle>
                <DialogDescription>
                    Esta acción no se puede deshacer. El rol "{{ rolAEliminar?.nombre }}" se eliminará permanentemente.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="confirmarEliminar">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
