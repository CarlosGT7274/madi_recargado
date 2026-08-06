<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, FileSpreadsheet, Pencil, Plus, Trash2 } from '@lucide/vue';
import { reactive, ref } from 'vue';
import ActividadController from '@/actions/App/Http/Controllers/Ingenierias/ActividadController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type ActividadOrigen = 'manual' | 'cotizacion';

export interface ActividadNodo {
    id: number;
    codigo: string | null;
    nombre: string;
    notas: string | null;
    origen: ActividadOrigen;
    hijas: ActividadNodo[];
}

const props = defineProps<{
    planta: number;
    proyecto: number;
    actividades: ActividadNodo[];
}>();

const abiertos = reactive<Record<number, boolean>>({});

function toggle(id: number): void {
    abiertos[id] = !abiertos[id];
}

const dialogOpen = ref(false);
const editando = ref<ActividadNodo | null>(null);
const parentId = ref<number | null>(null);

const form = reactive({ codigo: '', nombre: '', notas: '' });

function esManual(nodo: ActividadNodo): boolean {
    return nodo.origen === 'manual';
}

function abrirNueva(parent: number | null = null): void {
    editando.value = null;
    parentId.value = parent;
    form.codigo = '';
    form.nombre = '';
    form.notas = '';
    dialogOpen.value = true;
}

function abrirEditar(nodo: ActividadNodo): void {
    if (!esManual(nodo)) return;

    editando.value = nodo;
    parentId.value = null;
    form.codigo = nodo.codigo ?? '';
    form.nombre = nodo.nombre;
    form.notas = nodo.notas ?? '';
    dialogOpen.value = true;
}

function guardar(): void {
    if (editando.value) {
        router.put(
            ActividadController.update({ planta: props.planta, proyecto: props.proyecto, actividad: editando.value.id }).url,
            { codigo: form.codigo || null, nombre: form.nombre, notas: form.notas || null },
            { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) },
        );
        return;
    }

    router.post(
        ActividadController.store({ planta: props.planta, proyecto: props.proyecto }).url,
        {
            parent_id: parentId.value,
            codigo: form.codigo || null,
            nombre: form.nombre,
            notas: form.notas || null,
        },
        { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) },
    );
}

function eliminar(nodo: ActividadNodo): void {
    if (!esManual(nodo)) return;
    if (!confirm(`¿Eliminar "${nodo.nombre}" y sus sub-actividades?`)) return;

    router.delete(
        ActividadController.destroy({ planta: props.planta, proyecto: props.proyecto, actividad: nodo.id }).url,
        { preserveScroll: true },
    );
}
</script>

<template>
    <div class="space-y-2">
        <div v-for="nodo in actividades" :key="nodo.id" class="rounded-xl border">
            <div class="flex items-center gap-2 px-3 py-2">
                <button v-if="nodo.hijas.length" type="button" class="text-muted-foreground" @click="toggle(nodo.id)">
                    <ChevronDown v-if="abiertos[nodo.id]" class="size-4" />
                    <ChevronRight v-else class="size-4" />
                </button>
                <span v-else class="inline-block w-4" />

                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-1.5 truncate text-sm font-medium">
                        <span v-if="nodo.codigo" class="text-muted-foreground">{{ nodo.codigo }} · </span>
                        {{ nodo.nombre }}
                        <span v-if="!esManual(nodo)"
                            class="inline-flex items-center gap-1 rounded-full bg-violet-500/10 px-1.5 py-0.5 text-[10px] font-medium text-violet-600"
                            title="Proviene de una cotización aprobada">
                            <FileSpreadsheet class="size-3" />
                            Cotización
                        </span>
                    </p>
                    <p v-if="nodo.notas" class="truncate text-xs text-muted-foreground">{{ nodo.notas }}</p>
                </div>

                <template v-if="esManual(nodo)">
                    <Button variant="ghost" size="icon-sm" title="Agregar sub-actividad" @click="abrirNueva(nodo.id)">
                        <Plus class="size-3.5" />
                    </Button>
                    <Button variant="ghost" size="icon-sm" title="Editar" @click="abrirEditar(nodo)">
                        <Pencil class="size-3.5" />
                    </Button>
                    <Button variant="ghost" size="icon-sm" title="Eliminar" class="text-destructive"
                        @click="eliminar(nodo)">
                        <Trash2 class="size-3.5" />
                    </Button>
                </template>
            </div>

            <div v-if="abiertos[nodo.id] && nodo.hijas.length" class="space-y-2 border-t bg-muted/20 p-2 pl-6">
                <ActividadesArbol :planta="planta" :proyecto="proyecto" :actividades="nodo.hijas" />
            </div>
        </div>

        <p v-if="!actividades.length"
            class="rounded-xl border border-dashed py-6 text-center text-sm text-muted-foreground">
            Aún no hay actividades registradas.
        </p>

        <Button variant="outline" size="sm" @click="abrirNueva(null)">
            <Plus class="mr-2 size-4" />
            Nueva actividad
        </Button>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editando ? 'Editar actividad' : 'Nueva actividad' }}</DialogTitle>
                    <DialogDescription>
                        {{ parentId ? 'Se agregará como sub-actividad.' : 'Actividad de nivel raíz.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="act-codigo">Código (opcional)</Label>
                        <Input id="act-codigo" v-model="form.codigo" placeholder="Ej. A-01" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="act-nombre">Nombre</Label>
                        <Input id="act-nombre" v-model="form.nombre" placeholder="Nombre de la actividad" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="act-notas">Notas (opcional)</Label>
                        <Textarea id="act-notas" v-model="form.notas" rows="3" class="resize-none" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>
                    <Button :disabled="!form.nombre" @click="guardar">Guardar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
