<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Form } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import UsuarioController from '@/actions/App/Http/Controllers/Seguridad/UsuarioController';
import { index as usuariosIndex } from '@/routes/seguridad/usuarios';
import InputError from '@/components/InputError.vue';
import PageLayout from '@/components/PageLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import type { RolResumen, UsuarioResumen } from '@/types/usuarios';

const props = defineProps<{
    usuarios: UsuarioResumen[];
    rolesDisponibles: RolResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: usuariosIndex() }],
    },
});

const dialogCrearAbierto = ref(false);
const usuarioEditando = ref<UsuarioResumen | null>(null);

function abrirEdicion(usuario: UsuarioResumen) {
    usuarioEditando.value = usuario;
}

function cerrarEdicion() {
    usuarioEditando.value = null;
}

const rolesSeleccionadosCrear = ref<number[]>([]);
const rolesSeleccionadosEditar = computed<number[]>({
    get: () => usuarioEditando.value?.roles.map((r) => r.id) ?? [],
    set: () => { },
});
const rolesEditarLocal = ref<number[]>([]);

function alAbrirEdicion(usuario: UsuarioResumen) {
    rolesEditarLocal.value = usuario.roles.map((r) => r.id);
    abrirEdicion(usuario);
}

function eliminar(usuario: UsuarioResumen) {
    if (!confirm(`¿Eliminar a ${usuario.name}? Esta acción no se puede deshacer.`)) return;
    router.delete(UsuarioController.destroy(usuario.id).url, { preserveScroll: true });
}
</script>

<template>

    <Head title="Usuarios" />

    <PageLayout title="Usuarios" description="Alta y administración de usuarios del sistema"
        endpoint="seguridad.usuarios" with-create @create="dialogCrearAbierto = true">
        <Dialog v-model:open="dialogCrearAbierto">
            <DialogContent>
                <Form v-bind="UsuarioController.store.form()"
                    :transform="(data) => ({ ...data, roles: rolesSeleccionadosCrear })" reset-on-success
                    :options="{ preserveScroll: true }" @success="
                        dialogCrearAbierto = false;
                    rolesSeleccionadosCrear = [];
                    " v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Nuevo usuario</DialogTitle>
                        <DialogDescription>Da de alta un usuario y asígnale roles.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" name="name" placeholder="Nombre completo" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Correo</Label>
                        <Input id="email" name="email" type="email" placeholder="correo@empresa.com" />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Contraseña</Label>
                        <Input id="password" name="password" type="password" />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Roles</Label>
                        <div class="flex flex-wrap gap-3 rounded-lg border p-3">
                            <label v-for="rol in rolesDisponibles" :key="rol.id"
                                class="flex items-center gap-2 text-sm">
                                <Checkbox :model-value="rolesSeleccionadosCrear.includes(rol.id)" @update:model-value="
                                    (v) => {
                                        rolesSeleccionadosCrear = v
                                            ? [...rolesSeleccionadosCrear, rol.id]
                                            : rolesSeleccionadosCrear.filter((id) => id !== rol.id);
                                    }
                                " />
                                {{ rol.nombre }}
                            </label>
                            <p v-if="!rolesDisponibles.length" class="text-xs text-muted-foreground">
                                No hay roles activos todavía.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog :open="usuarioEditando !== null" @update:open="(v) => !v && cerrarEdicion()">
            <DialogContent v-if="usuarioEditando">
                <Form v-bind="UsuarioController.update.form(usuarioEditando.id)"
                    :transform="(data) => ({ ...data, roles: rolesEditarLocal })" :options="{ preserveScroll: true }"
                    @success="cerrarEdicion" v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Editar usuario</DialogTitle>
                        <DialogDescription>{{ usuarioEditando.email }}</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit_name">Nombre</Label>
                        <Input id="edit_name" name="name" :default-value="usuarioEditando.name" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_email">Correo</Label>
                        <Input id="edit_email" name="email" type="email" :default-value="usuarioEditando.email" />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_password">Nueva contraseña (opcional)</Label>
                        <Input id="edit_password" name="password" type="password"
                            placeholder="Dejar en blanco para no cambiar" />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Roles</Label>
                        <div class="flex flex-wrap gap-3 rounded-lg border p-3">
                            <label v-for="rol in rolesDisponibles" :key="rol.id"
                                class="flex items-center gap-2 text-sm">
                                <Checkbox :model-value="rolesEditarLocal.includes(rol.id)" @update:model-value="
                                    (v) => {
                                        rolesEditarLocal = v
                                            ? [...rolesEditarLocal, rol.id]
                                            : rolesEditarLocal.filter((id) => id !== rol.id);
                                    }
                                " />
                                {{ rol.nombre }}
                            </label>
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary" @click="cerrarEdicion">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="overflow-hidden rounded-xl border">
            <div
                class="grid grid-cols-[1fr_1fr_1fr_100px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                <span>Nombre</span>
                <span>Correo</span>
                <span>Roles</span>
                <span class="text-right">Acciones</span>
            </div>

            <div v-for="usuario in usuarios" :key="usuario.id"
                class="grid grid-cols-[1fr_1fr_1fr_100px] items-center gap-2 border-t px-4 py-3 text-sm">
                <span class="font-medium">{{ usuario.name }}</span>
                <span class="text-muted-foreground">{{ usuario.email }}</span>
                <div class="flex flex-wrap gap-1">
                    <Badge v-for="rol in usuario.roles" :key="rol.id" variant="secondary" class="text-[10px]">
                        {{ rol.nombre }}
                    </Badge>
                    <span v-if="!usuario.roles.length" class="text-xs text-muted-foreground">Sin rol</span>
                </div>
                <div class="flex justify-end gap-1">
                    <Button variant="ghost" size="icon" @click="alAbrirEdicion(usuario)">
                        <Pencil class="size-4" />
                    </Button>
                    <Button variant="ghost" size="icon" class="text-destructive hover:bg-destructive/10"
                        @click="eliminar(usuario)">
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>

            <p v-if="!usuarios.length" class="border-t px-4 py-8 text-center text-sm text-muted-foreground">
                Aún no hay usuarios registrados.
            </p>
        </div>
    </PageLayout>
</template>
