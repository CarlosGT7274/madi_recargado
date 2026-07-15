<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import {
    destroy as destroyRole,
    permisos as permisosRole,
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
import { ACCIONES, tieneBit } from '@/lib/permisos';
import { index as rolesIndex } from '@/routes/roles';
import type { PermisoResumen, RoleResumen } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: rolesIndex(),
            },
        ],
    },
});

const props = defineProps<{
    roles: RoleResumen[];
    permisosDisponibles: PermisoResumen[];
}>();

/* ---------- Crear / Editar rol ---------- */

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

/* ---------- Eliminar rol ---------- */

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

/* ---------- Asignar permisos ---------- */

const rolPermisos = ref<RoleResumen | null>(null);
const dialogPermisosAbierto = ref(false);
const matrizPermisos = reactive<Record<number, number>>({});
const permisosProcesando = ref(false);

function abrirPermisos(role: RoleResumen) {
    rolPermisos.value = role;

    props.permisosDisponibles.forEach((permiso) => {
        matrizPermisos[permiso.id] = role.permisos[permiso.id] ?? 0;
    });

    dialogPermisosAbierto.value = true;
}

function alternar(permisoId: number, bit: number, activo: boolean) {
    const actual = matrizPermisos[permisoId] ?? 0;
    matrizPermisos[permisoId] = activo ? actual | bit : actual & ~bit;
}

function guardarPermisos() {
    if (!rolPermisos.value) {
        return;
    }

    permisosProcesando.value = true;

    router.put(
        permisosRole.url(rolPermisos.value.id),
        { permisos: matrizPermisos },
        {
            preserveScroll: true,
            onFinish: () => {
                permisosProcesando.value = false;
            },
            onSuccess: () => {
                dialogPermisosAbierto.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Roles" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Roles"
                description="Administra los roles del sistema y sus permisos"
            />

            <Dialog v-model:open="dialogRolAbierto">
                <DialogTrigger as-child>
                    <Button @click="abrirCrear">Nuevo rol</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {{ rolEnEdicion ? 'Editar rol' : 'Nuevo rol' }}
                        </DialogTitle>
                        <DialogDescription>
                            Define el nombre del rol y si estará disponible para asignarse a
                            usuarios.
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
                            <Button type="submit" :disabled="rolForm.processing">
                                Guardar
                            </Button>
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
                        <td class="px-4 py-2">
                            {{ role.activo ? 'Activo' : 'Inactivo' }}
                        </td>
                        <td class="px-4 py-2">{{ role.usuarios_count }}</td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="sm" @click="abrirPermisos(role)">
                                    Permisos
                                </Button>
                                <Button variant="outline" size="sm" @click="abrirEditar(role)">
                                    Editar
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="rolAEliminar = role"
                                >
                                    Eliminar
                                </Button>
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

    <!-- Confirmar eliminación -->
    <Dialog
        :open="!!rolAEliminar"
        @update:open="(open) => { if (!open) rolAEliminar = null; }"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Eliminar rol?</DialogTitle>
                <DialogDescription>
                    Esta acción no se puede deshacer. El rol "{{ rolAEliminar?.nombre }}" se
                    eliminará permanentemente.
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

    <!-- Asignar permisos -->
    <Dialog v-model:open="dialogPermisosAbierto">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Permisos de {{ rolPermisos?.nombre }}</DialogTitle>
                <DialogDescription>
                    Selecciona las acciones permitidas por módulo.
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-96 overflow-y-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Módulo</th>
                            <th
                                v-for="accion in ACCIONES"
                                :key="accion.bit"
                                class="px-4 py-2 text-center font-medium"
                            >
                                {{ accion.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="permiso in permisosDisponibles"
                            :key="permiso.id"
                            class="border-t"
                        >
                            <td class="px-4 py-2">{{ permiso.nombre }}</td>
                            <td
                                v-for="accion in ACCIONES"
                                :key="accion.bit"
                                class="px-4 py-2 text-center"
                            >
                                <Checkbox
                                    :checked="tieneBit(matrizPermisos[permiso.id] ?? 0, accion.bit)"
                                    @update:checked="
                                        (valor) => alternar(permiso.id, accion.bit, valor === true)
                                    "
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button :disabled="permisosProcesando" @click="guardarPermisos">
                    Guardar permisos
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
