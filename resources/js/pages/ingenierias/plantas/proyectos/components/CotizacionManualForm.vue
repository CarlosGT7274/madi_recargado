<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface PartidaManual {
    descripcion: string;
    unidad: string;
    cantidad: number;
    precio_unitario: number;
}

interface CategoriaManual {
    descripcion: string;
    partidas: PartidaManual[];
}

interface CotizacionManualFormData {
    fecha: string;
    cliente: string;
    direccion: string;
    obra: string;
    vendedor: string;
    proveedor: string;
    iva: number;
    notas: string;
    categorias: CategoriaManual[];
    [key: string]: string | number | CategoriaManual[];
}

const props = defineProps<{
    planta: { id: number };
    proyecto: { id: number };
}>();

const emit = defineEmits<{
    (e: 'success'): void;
}>();

function partidaVacia(): PartidaManual {
    return { descripcion: '', unidad: '', cantidad: 1, precio_unitario: 0 };
}

function categoriaVacia(): CategoriaManual {
    return { descripcion: '', partidas: [partidaVacia()] };
}

const form = useForm<CotizacionManualFormData>({
    fecha: new Date().toISOString().slice(0, 10),
    cliente: '',
    direccion: '',
    obra: '',
    vendedor: '',
    proveedor: '',
    iva: 0,
    notas: '',
    categorias: [categoriaVacia()],
});

function agregarCategoria(): void {
    form.categorias.push(categoriaVacia());
}

function quitarCategoria(indiceCategoria: number): void {
    if (form.categorias.length <= 1) return;
    form.categorias.splice(indiceCategoria, 1);
}

function agregarPartida(indiceCategoria: number): void {
    form.categorias[indiceCategoria].partidas.push(partidaVacia());
}

function quitarPartida(indiceCategoria: number, indicePartida: number): void {
    const categoria = form.categorias[indiceCategoria];
    if (categoria.partidas.length <= 1) return;
    categoria.partidas.splice(indicePartida, 1);
}

function errorDe(campo: string): string | undefined {
    return (form.errors as Record<string, string>)[campo];
}

function guardar(): void {
    form.post(
        CotizacionController.storeManualProyecto([props.planta.id, props.proyecto.id]).url,
        {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        },
    );
}
</script>

<template>
    <div class="space-y-6 rounded-xl border bg-card p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="manual-fecha">Fecha</Label>
                <Input id="manual-fecha" type="date" v-model="form.fecha" />
                <InputError :message="errorDe('fecha')" />
            </div>
            <div class="grid gap-2">
                <Label for="manual-obra">Obra</Label>
                <Input id="manual-obra" v-model="form.obra" placeholder="Nombre de la obra" />
                <InputError :message="errorDe('obra')" />
            </div>
            <div class="grid gap-2">
                <Label for="manual-cliente">Cliente</Label>
                <Input id="manual-cliente" v-model="form.cliente" />
                <InputError :message="errorDe('cliente')" />
            </div>
            <div class="grid gap-2">
                <Label for="manual-direccion">Dirección</Label>
                <Input id="manual-direccion" v-model="form.direccion" />
                <InputError :message="errorDe('direccion')" />
            </div>
            <div class="grid gap-2">
                <Label for="manual-vendedor">Vendedor</Label>
                <Input id="manual-vendedor" v-model="form.vendedor" />
                <InputError :message="errorDe('vendedor')" />
            </div>
            <div class="grid gap-2">
                <Label for="manual-proveedor">Proveedor</Label>
                <Input id="manual-proveedor" v-model="form.proveedor" />
                <InputError :message="errorDe('proveedor')" />
            </div>
            <div class="grid gap-2">
                <Label for="manual-iva">IVA</Label>
                <Input id="manual-iva" type="number" min="0" step="0.01" v-model.number="form.iva" />
                <InputError :message="errorDe('iva')" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="manual-notas">Notas</Label>
            <Textarea id="manual-notas" v-model="form.notas" rows="2" />
            <InputError :message="errorDe('notas')" />
        </div>

        <div class="space-y-4">
            <div v-for="(categoria, indiceCategoria) in form.categorias" :key="indiceCategoria"
                class="rounded-lg border p-4">
                <div class="mb-3 flex items-end gap-2">
                    <div class="flex-1 grid gap-2">
                        <Label :for="`categoria-${indiceCategoria}`">Categoría {{ indiceCategoria + 1 }}</Label>
                        <Input :id="`categoria-${indiceCategoria}`" v-model="categoria.descripcion"
                            placeholder="Ej. PRELIMINARES" />
                        <InputError :message="errorDe(`categorias.${indiceCategoria}.descripcion`)" />
                    </div>
                    <Button type="button" variant="ghost" size="icon" class="text-destructive hover:bg-destructive/10"
                        :disabled="form.categorias.length <= 1" @click="quitarCategoria(indiceCategoria)">
                        <Trash2 class="size-4" />
                    </Button>
                </div>

                <div class="space-y-2">
                    <div v-for="(partida, indicePartida) in categoria.partidas" :key="indicePartida"
                        class="grid grid-cols-[1fr_100px_100px_120px_36px] items-start gap-2">
                        <div>
                            <Input v-model="partida.descripcion"
                                :placeholder="`${indiceCategoria + 1}.${indicePartida + 1} Descripción`" />
                            <InputError
                                :message="errorDe(`categorias.${indiceCategoria}.partidas.${indicePartida}.descripcion`)" />
                        </div>
                        <Input v-model="partida.unidad" placeholder="Unidad" />
                        <Input type="number" min="0.01" step="0.01" v-model.number="partida.cantidad"
                            placeholder="Cant." />
                        <Input type="number" min="0" step="0.01" v-model.number="partida.precio_unitario"
                            placeholder="P. Unitario" />
                        <Button type="button" variant="ghost" size="icon"
                            class="text-destructive hover:bg-destructive/10" :disabled="categoria.partidas.length <= 1"
                            @click="quitarPartida(indiceCategoria, indicePartida)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <Button type="button" variant="outline" size="sm" class="mt-3" @click="agregarPartida(indiceCategoria)">
                    <Plus class="mr-1.5 size-3.5" />
                    Agregar partida
                </Button>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <Button type="button" variant="outline" size="sm" @click="agregarCategoria">
                <Plus class="mr-1.5 size-3.5" />
                Agregar categoría
            </Button>

            <Button :disabled="form.processing" @click="guardar">
                Guardar cotización
            </Button>
        </div>
    </div>
</template>
