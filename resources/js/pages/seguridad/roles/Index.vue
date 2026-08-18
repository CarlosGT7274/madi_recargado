<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Shield, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';
import RoleController from '@/actions/App/Http/Controllers/Seguridad/RoleController';
import { index as rolesIndex } from '@/routes/seguridad/roles';
import InputError from '@/components/InputError.vue';
import PageLayout from '@/components/PageLayout.vue';
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
import { toast } from 'vue-sonner';
import type { RoleResumen } from '@/types/roles';

defineProps<{
    roles: RoleResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Roles', href: rolesIndex() }],
    },
});

const dialogOpen = ref(false);

// ---------- Editar nombre del rol ----------
const roleEditando = ref<RoleResumen | null>(null);

function abrirEdicion(role: RoleResumen) {
    roleEditando.value = role;
}

function cerrarEdicion() {
    roleEditando.value = null;
}

// ---------- Eliminar rol ----------
function eliminar(role: RoleResumen) {
    if (!confirm(`¿Eliminar el rol "${role.nombre}"? Esta acción no se puede deshacer.`)) return;

    router.delete(RoleController.destroy(role.id).url, {
        preserveScroll: true,
        onError: (errors) => {
            toast.error(errors.role ?? 'No se pudo eliminar el rol.');
        },
    });
}
</script>

<template>

    <Head title="Roles" />

    <PageLayout title="Roles" description="Roles y permisos del sistema" endpoint="seguridad.roles" with-create
        @create="dialogOpen = true">
        <!-- Dialog: Crear rol -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <Form v-bind="RoleController.store.form()" reset-on-success :options="{ preserveScroll: true }"
                    @success="dialogOpen = false" v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Nuevo rol</DialogTitle>
                        <DialogDescription>Crea un nuevo rol para asignar permisos.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="nombre">Nombre</Label>
                        <Input id="nombre" name="nombre" placeholder="Nombre del rol" />
                        <InputError :message="errors.nombre" />
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

        <!-- Dialog: Editar nombre del rol -->
        <Dialog :open="roleEditando !== null" @update:open="(v) => !v && cerrarEdicion()">
            <DialogContent v-if="roleEditando">
                <Form v-bind="RoleController.update.form(roleEditando.id)" :options="{ preserveScroll: true }"
                    @success="cerrarEdicion" v-slot="{ errors, processing }" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Editar rol</DialogTitle>
                        <DialogDescription>Actualiza el nombre del rol.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit_nombre">Nombre</Label>
                        <Input id="edit_nombre" name="nombre" :default-value="roleEditando.nombre" />
                        <InputError :message="errors.nombre" />
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

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="role in roles" :key="role.id"
                class="group relative flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-colors hover:bg-accent/50">
                <!-- Stretched link para navegar a los permisos sin envolver los botones en un <a> -->
                <Link :href="`/seguridad/roles/${role.id}`" class="absolute inset-0 rounded-2xl">
                    <span class="sr-only">Ver y editar permisos de {{ role.nombre }}</span>
                </Link>

                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Shield class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold leading-tight">{{ role.nombre }}</p>
                            <p class="flex items-center gap-1 text-xs text-muted-foreground">
                                <Users class="size-3" />
                                {{ role.usuarios_count }} {{ role.usuarios_count === 1 ? 'usuario' : 'usuarios' }}
                            </p>
                        </div>
                    </div>

                    <div class="relative z-10 flex items-center gap-1">
                        <Button variant="ghost" size="icon" class="size-8" aria-label="Editar rol"
                            @click="abrirEdicion(role)">
                            <Pencil class="size-4" />
                        </Button>
                        <Button variant="ghost" size="icon" class="size-8 text-destructive hover:bg-destructive/10"
                            aria-label="Eliminar rol" @click="eliminar(role)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="mt-auto flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
                    <span>Ver y editar permisos →</span>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                        :class="role.activo ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">
                        {{ role.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>

            <p v-if="!roles.length"
                class="col-span-full rounded-2xl border py-12 text-center text-sm text-muted-foreground">
                Aún no hay roles registrados.
            </p>
        </div>
    </PageLayout>
</template>
