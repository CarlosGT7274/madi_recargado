<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import RoleController from '@/actions/App/Http/Controllers/Seguridad/RoleController';
import { index as rolesIndex } from '@/routes/seguridad/roles';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { Form } from '@inertiajs/vue3';
import type { RoleResumen } from '@/types/roles';

defineProps<{
    roles: RoleResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Roles', href: rolesIndex() },
        ],
    },
});

const dialogOpen = ref(false);
</script>

<template>
    <Head title="Roles" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Heading
                title="Roles"
                description="Roles y permisos del sistema"
            />

            <Dialog v-model:open="dialogOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Nuevo rol
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="RoleController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        @success="dialogOpen = false"
                        v-slot="{ errors, processing }"
                        class="space-y-4"
                    >
                        <DialogHeader>
                            <DialogTitle>Nuevo rol</DialogTitle>
                            <DialogDescription>
                                Crea un nuevo rol para asignar permisos.
                            </DialogDescription>
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
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div
                class="grid grid-cols-[1fr_100px_120px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground"
            >
                <span>Nombre</span>
                <span>Estado</span>
                <span class="text-right">Usuarios</span>
            </div>

            <Link
                v-for="role in roles"
                :key="role.id"
                :href="`/seguridad/roles/${role.id}`"
                class="grid grid-cols-[1fr_100px_120px] items-center gap-2 border-t px-4 py-3 text-sm hover:bg-accent"
            >
                <span class="font-medium">{{ role.nombre }}</span>
                <span :class="role.activo ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
                    {{ role.activo ? 'Activo' : 'Inactivo' }}
                </span>
                <span class="text-right text-muted-foreground">{{ role.usuarios_count }}</span>
            </Link>

            <p v-if="!roles.length" class="border-t px-4 py-8 text-center text-sm text-muted-foreground">
                Aún no hay roles registrados.
            </p>
        </div>
    </div>
</template>
