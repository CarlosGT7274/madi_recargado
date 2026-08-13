<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2, UserCog, UserRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { RolResumen, UsuarioResumen } from '@/types/usuarios';

interface EmpleadoSinCuenta {
    id: number;
    nombre: string;
    puesto: string | null;
    activo: boolean;
}

const props = defineProps<{
    usuarios: UsuarioResumen[];
    empleadosSinCuenta: EmpleadoSinCuenta[];
    rolesDisponibles: RolResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: usuariosIndex() }],
    },
});

// ---------- Crear: switch Usuario (con acceso) vs Empleado (sin acceso) ----------

const dialogCrearAbierto = ref(false);
const tipoCrear = ref<'usuario' | 'empleado'>('usuario');
const rolSeleccionadoCrear = ref<string | undefined>(undefined);

watch(dialogCrearAbierto, (abierto) => {
    if (!abierto) {
        tipoCrear.value = 'usuario';
        rolSeleccionadoCrear.value = undefined;
    }
});

// ---------- Editar usuario (siempre tiene acceso) ----------

const usuarioEditando = ref<UsuarioResumen | null>(null);
const rolSeleccionadoEditar = ref<string | undefined>(undefined);

function abrirEdicion(usuario: UsuarioResumen) {
    rolSeleccionadoEditar.value = usuario.rolId ? String(usuario.rolId) : undefined;
    usuarioEditando.value = usuario;
}

function cerrarEdicion() {
    usuarioEditando.value = null;
}

function eliminar(usuario: UsuarioResumen) {
    if (!confirm(`¿Eliminar a ${usuario.name}? Esta acción no se puede deshacer.`)) return;
    router.delete(UsuarioController.destroy(usuario.id).url, { preserveScroll: true });
}

function eliminarEmpleado(empleado: EmpleadoSinCuenta) {
    if (!confirm(`¿Eliminar a ${empleado.nombre}? Esta acción no se puede deshacer.`)) return;
    router.delete(UsuarioController.destroyEmpleado(empleado.id).url, { preserveScroll: true });
}

const totalRegistros = computed(() => props.usuarios.length + props.empleadosSinCuenta.length);
</script>

<template>

    <Head title="Usuarios" />

    <PageLayout title="Usuarios" description="Alta y administración de usuarios y empleados del sistema"
        endpoint="seguridad.usuarios" with-create @create="dialogCrearAbierto = true">

        <!-- Dialog: Crear (Usuario con acceso | Empleado sin acceso) -->
        <Dialog v-model:open="dialogCrearAbierto">
            <DialogContent>
                <Form v-bind="UsuarioController.store.form()" :transform="(data) => ({
                    ...data,
                    tipo: tipoCrear,
                    rol_id: rolSeleccionadoCrear ? Number(rolSeleccionadoCrear) : null,
                })" reset-on-success :options="{ preserveScroll: true }" @success="dialogCrearAbierto = false"
                    v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Nuevo registro</DialogTitle>
                        <DialogDescription>
                            Elige si es un usuario con acceso al sistema o solo un empleado (sin login).
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Switch tipo -->
                    <div class="grid grid-cols-2 gap-2 rounded-lg border p-1">
                        <button type="button"
                            class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                            :class="tipoCrear === 'usuario' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                            @click="tipoCrear = 'usuario'">
                            <UserCog class="size-4" />
                            Usuario del sistema
                        </button>
                        <button type="button"
                            class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                            :class="tipoCrear === 'empleado' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                            @click="tipoCrear = 'empleado'">
                            <UserRound class="size-4" />
                            Empleado (sin acceso)
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="tipoCrear === 'usuario'">
                            Podrá iniciar sesión y se le aplican permisos según el rol asignado.
                        </template>
                        <template v-else>
                            Solo se registra como empleado asignable (Planeación, Nómina, etc). No podrá iniciar
                            sesión ni tiene permisos.
                        </template>
                    </p>

                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" name="name" placeholder="Nombre completo" />
                        <InputError :message="errors.name" />
                    </div>

                    <template v-if="tipoCrear === 'usuario'">
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
                            <Label for="password_confirmation">Confirmar contraseña</Label>
                            <Input id="password_confirmation" name="password_confirmation" type="password" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Rol</Label>
                            <Select v-model="rolSeleccionadoCrear">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Sin rol asignado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="rol in rolesDisponibles" :key="rol.id" :value="String(rol.id)">
                                        {{ rol.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="!rolesDisponibles.length" class="text-xs text-muted-foreground">
                                No hay roles activos todavía.
                            </p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="grid gap-2">
                            <Label for="puesto">Puesto</Label>
                            <Input id="puesto" name="puesto" placeholder="Ej. Ayudante general" />
                            <InputError :message="errors.puesto" />
                        </div>
                    </template>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <!-- Dialog: Editar usuario -->
        <Dialog :open="usuarioEditando !== null" @update:open="(v) => !v && cerrarEdicion()">
            <DialogContent v-if="usuarioEditando">
                <Form v-bind="UsuarioController.update.form(usuarioEditando.id)" :transform="(data) => ({
                    ...data,
                    rol_id: rolSeleccionadoEditar ? Number(rolSeleccionadoEditar) : null,
                })" :options="{ preserveScroll: true }" @success="cerrarEdicion" v-slot="{ errors, processing }"
                    class="space-y-4">
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
                        <Label for="edit_password_confirmation">Confirmar nueva contraseña</Label>
                        <Input id="edit_password_confirmation" name="password_confirmation" type="password" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Rol</Label>
                        <Select v-model="rolSeleccionadoEditar">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Sin rol asignado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="rol in rolesDisponibles" :key="rol.id" :value="String(rol.id)">
                                    {{ rol.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
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

        <p v-if="!totalRegistros" class="rounded-xl border px-4 py-8 text-center text-sm text-muted-foreground">
            Aún no hay usuarios ni empleados registrados.
        </p>

        <div v-if="usuarios.length" class="space-y-2">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                <UserCog class="size-3.5" /> Usuarios con acceso
            </p>
            <div class="overflow-hidden rounded-xl border">
                <div
                    class="grid grid-cols-[1fr_1fr_1fr_100px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                    <span>Nombre</span>
                    <span>Correo</span>
                    <span>Rol</span>
                    <span class="text-right">Acciones</span>
                </div>

                <div v-for="usuario in usuarios" :key="usuario.id"
                    class="grid grid-cols-[1fr_1fr_1fr_100px] items-center gap-2 border-t px-4 py-3 text-sm">
                    <span class="font-medium">{{ usuario.name }}</span>
                    <span class="text-muted-foreground">{{ usuario.email }}</span>
                    <div>
                        <Badge v-if="usuario.roles.length" variant="secondary" class="text-[10px]">
                            {{ usuario.roles[0].nombre }}
                        </Badge>
                        <span v-else class="text-xs text-muted-foreground">Sin rol</span>
                    </div>
                    <div class="flex justify-end gap-1">
                        <Button variant="ghost" size="icon" @click="abrirEdicion(usuario)">
                            <Pencil class="size-4" />
                        </Button>
                        <Button variant="ghost" size="icon" class="text-destructive hover:bg-destructive/10"
                            @click="eliminar(usuario)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="empleadosSinCuenta.length" class="mt-6 space-y-2">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                <UserRound class="size-3.5" /> Empleados sin acceso al sistema
            </p>
            <div class="overflow-hidden rounded-xl border">
                <div
                    class="grid grid-cols-[1fr_1fr_100px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                    <span>Nombre</span>
                    <span>Puesto</span>
                    <span class="text-right">Acciones</span>
                </div>

                <div v-for="empleado in empleadosSinCuenta" :key="empleado.id"
                    class="grid grid-cols-[1fr_1fr_100px] items-center gap-2 border-t px-4 py-3 text-sm">
                    <span class="font-medium">{{ empleado.nombre }}</span>
                    <span class="text-muted-foreground">{{ empleado.puesto ?? '—' }}</span>
                    <div class="flex justify-end gap-1">
                        <Button variant="ghost" size="icon" class="text-destructive hover:bg-destructive/10"
                            @click="eliminarEmpleado(empleado)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
