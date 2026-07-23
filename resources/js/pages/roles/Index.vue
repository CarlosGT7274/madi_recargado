
<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    destroy as destroyRole,
    store as storeRole,
    update as updateRole,
} from '@/actions/App/Http/Controllers/RoleController';
import { KeyRound, Pencil, Plus, Trash2, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading title="Roles" description="Administra los roles del sistema y sus permisos" />
            <Dialog v-model:open="dialogRolAbierto">
                <DialogTrigger as-child>
                    <Button class="gap-2" @click="abrirCrear">
                        <Plus class="size-4" />
                        Nuevo rol
                    </Button>
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
        <Card class="overflow-hidden py-0 shadow-sm">
            <CardHeader class="border-b bg-muted/30 py-4">
                <CardTitle class="text-base">Roles del sistema</CardTitle>
                <CardDescription>{{ roles.length }} rol(es) registrados</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">Nombre</th>
                            <th class="px-6 py-3 font-semibold">Estado</th>
                            <th class="px-6 py-3 font-semibold">Usuarios</th>
                            <th class="px-6 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="role in roles"
                            :key="role.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/40"
                        >
                            <td class="px-6 py-4 font-semibold text-foreground">{{ role.nombre }}</td>
                            <td class="px-6 py-4">
                                <Badge :variant="role.activo ? 'default' : 'secondary'">
                                    {{ role.activo ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                                    <Users class="size-4" />
                                    <span class="tabular-nums">{{ role.usuarios_count }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link :href="rolesShow(role.id)">
                                        <Button variant="outline" size="sm" class="gap-1.5">
                                            <KeyRound class="size-3.5" />
                                            Permisos
                                        </Button>
                                    </Link>
                                    <Button variant="outline" size="sm" class="gap-1.5" @click="abrirEditar(role)">
                                        <Pencil class="size-3.5" />
                                        Editar
                                    </Button>
                                    <Button variant="destructive" size="sm" class="gap-1.5" @click="rolAEliminar = role">
                                        <Trash2 class="size-3.5" />
                                        Eliminar
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="roles.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground">
                                Aún no hay roles registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
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

