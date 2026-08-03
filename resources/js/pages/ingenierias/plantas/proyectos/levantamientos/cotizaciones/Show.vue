<script lang="ts">
import { usePage } from '@inertiajs/vue3';
import { breadcrumbsCotizacion, type CotizacionRef, type LevantamientoRef, type PlantaRef, pageLayout } from '@/lib/breadcrumbs';

interface Props {
    planta: PlantaRef;
    levantamiento: LevantamientoRef;
    cotizacion: CotizacionRef;
}

export default pageLayout(() => {
    const { planta, levantamiento, cotizacion } = usePage<Props>().props;
    return breadcrumbsCotizacion(planta, levantamiento, cotizacion);
});
</script>

<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, Calendar, FileSpreadsheet, MapPin, Download, Plus, Upload } from '@lucide/vue';
import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import PartidaController from '@/actions/App/Http/Controllers/Ingenierias/PartidaController';
import PageLayout from '@/components/PageLayout.vue';
import InputError from '@/components/InputError.vue';
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

type PlantaResumen = {
    id: number;
    nombre: string;
};

type LevantamientoResumen = {
    id: number;
    folio: string;
};

type CotizacionDetalle = {
    id: number;
    levantamiento_id: number;
    folio: string;
    fecha: string | null;
    para: string | null;
    cliente: string | null;
    direccion: string | null;
    obra: string | null;
    vendedor: string | null;
    proveedor: string | null;
    correo_vendedor: string | null;
    subtotal: number | null;
    iva: number | null;
    total: number | null;
    moneda: string | null;
    tiempo_entrega: string | null;
    dias_credito: string | null;
    vigencia_cotizacion: string | null;
    notas: string | null;
    estado: string;
    creado: string | null;
    modificado: string | null;
};

interface PartidaResumen {
    id: number;
    numeroPartida: number;
    descripcion: string;
    cantidad: number;
    unidad: string | null;
    precioUnitario: number;
    importe: number;
    costoHora: number | null;
}

const props = defineProps<{
    planta: PlantaResumen;
    levantamiento: LevantamientoResumen;
    cotizacion: CotizacionDetalle;
    partidas: PartidaResumen[];
}>();

const nuevaPartidaDialogOpen = ref(false);
const archivoPartidasInput = ref<HTMLInputElement | null>(null);

function subirPlantillaPartidas(): void {
    const archivo = archivoPartidasInput.value?.files?.[0];
    if (!archivo) return;

    router.post(
        PartidaController.importar({ planta: props.planta.id, proyecto: props.levantamiento.id, levantamiento: props.levantamiento.id, cotizacion: props.cotizacion.id }).url,
        { archivo },
        { forceFormData: true },
    );
}

function formatoMonedaPartida(valor: number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}

const editDialogOpen = ref(false);

function eliminarCotizacion() {
    if (!confirm(`¿Eliminar la cotización "${props.cotizacion.folio}"? Esta acción no se puede deshacer.`)) {
        return;
    }
    router.delete(
        CotizacionController.destroy({
            planta: props.planta.id,
            levantamiento: props.levantamiento.id,
            cotizacion: props.cotizacion.id,
        }).url,
        {
            onSuccess: () =>
                router.visit(LevantamientoController.show({ planta: props.planta.id, levantamiento: props.levantamiento.id }).url),
        },
    );
}

const estadoLabel: Record<string, string> = {
    borrador: 'Borrador',
    enviada: 'Enviada',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const estadoGrupo: Record<string, 'aprobado' | 'pendiente' | 'negativo'> = {
    borrador: 'pendiente',
    enviada: 'pendiente',
    aprobada: 'aprobado',
    rechazada: 'negativo',
};

function estadoBadgeClass(estado: string) {
    const grupo = estadoGrupo[estado] ?? 'pendiente';
    if (grupo === 'aprobado') return 'bg-emerald-500/10 text-emerald-600';
    if (grupo === 'negativo') return 'bg-red-500/10 text-red-600';
    return 'bg-amber-500/10 text-amber-600';
}

function formatoMoneda(valor: number | null) {
    if (valor === null) return '—';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
}
</script>

<template>
    <Head :title="`Cotización ${cotizacion.folio}`" />

    <PageLayout
        :title="cotizacion.folio"
        :description="cotizacion.cliente ?? ''"
        endpoint="ingenierias.plantas.levantamientos.cotizaciones"
        with-edit
        with-delete
        @edit="editDialogOpen = true"
        @delete="eliminarCotizacion"
    >
        <template #breadcrumbs>
            <Link
                :href="LevantamientoController.show({ planta: planta.id, levantamiento: levantamiento.id })"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
            </Link>
        </template>

        <!-- Dialog: editar cotización -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <Form
                    v-bind="
                        CotizacionController.update.form({
                            planta: planta.id,
                            levantamiento: levantamiento.id,
                            cotizacion: cotizacion.id,
                        })
                    "
                    :options="{ preserveScroll: true }"
                    @success="editDialogOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>Editar cotización</DialogTitle>
                        <DialogDescription>Actualiza los datos de esta cotización.</DialogDescription>
                    </DialogHeader>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-fecha">Fecha</Label>
                            <Input id="edit-fecha" name="fecha" type="date" :default-value="cotizacion.fecha ?? ''" />
                            <InputError :message="errors.fecha" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-para">Para</Label>
                            <Input id="edit-para" name="para" :default-value="cotizacion.para ?? ''" placeholder="Atención de..." />
                            <InputError :message="errors.para" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-cliente">Cliente</Label>
                        <Input id="edit-cliente" name="cliente" :default-value="cotizacion.cliente ?? ''" />
                        <InputError :message="errors.cliente" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-direccion">Dirección</Label>
                        <Input id="edit-direccion" name="direccion" :default-value="cotizacion.direccion ?? ''" placeholder="Dirección (opcional)" />
                        <InputError :message="errors.direccion" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-vendedor">Vendedor</Label>
                            <Input id="edit-vendedor" name="vendedor" :default-value="cotizacion.vendedor ?? ''" placeholder="Vendedor (opcional)" />
                            <InputError :message="errors.vendedor" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-proveedor">Proveedor</Label>
                            <Input id="edit-proveedor" name="proveedor" :default-value="cotizacion.proveedor ?? ''" placeholder="Proveedor (opcional)" />
                            <InputError :message="errors.proveedor" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-correo-vendedor">Correo Vendedor</Label>
                            <Input id="edit-correo-vendedor" name="correo_vendedor" type="email" :default-value="cotizacion.correo_vendedor ?? ''" placeholder="opcional" />
                            <InputError :message="errors.correo_vendedor" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-iva">IVA (monto)</Label>
                            <Input id="edit-iva" name="iva" type="number" step="0.01" :default-value="cotizacion.iva ?? 0" placeholder="0.00" />
                            <InputError :message="errors.iva" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-tiempo-entrega">Tiempo Entrega</Label>
                            <Input id="edit-tiempo-entrega" name="tiempo_entrega" :default-value="cotizacion.tiempo_entrega ?? ''" placeholder="opcional" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-dias-credito">Días Crédito</Label>
                            <Input id="edit-dias-credito" name="dias_credito" :default-value="cotizacion.dias_credito ?? ''" placeholder="opcional" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-vigencia">Vigencia</Label>
                            <Input id="edit-vigencia" name="vigencia_cotizacion" :default-value="cotizacion.vigencia_cotizacion ?? ''" placeholder="opcional" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-notas">Notas</Label>
                        <Input id="edit-notas" name="notas" :default-value="cotizacion.notas ?? ''" />
                        <InputError :message="errors.notas" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">Guardar cambios</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="space-y-6">
            <!-- Header -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b bg-muted/30 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <FileSpreadsheet class="size-6" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-lg font-semibold leading-tight">{{ cotizacion.folio }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium uppercase"
                                    :class="estadoBadgeClass(cotizacion.estado)"
                                >
                                    {{ estadoLabel[cotizacion.estado] ?? cotizacion.estado }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ cotizacion.cliente ?? '—' }}</p>
                        </div>
                    </div>

                    <Badge variant="secondary">{{ formatoMoneda(cotizacion.total) }}</Badge>
                </div>
            </div>

            <!-- Detalle -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <MapPin class="size-4" />
                        Datos generales
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Levantamiento</dt>
                            <dd class="font-medium">{{ levantamiento.folio }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Vendedor</dt>
                            <dd class="font-medium">{{ cotizacion.vendedor ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Proveedor</dt>
                            <dd class="font-medium">{{ cotizacion.proveedor ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Moneda</dt>
                            <dd class="font-medium">{{ cotizacion.moneda ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <FileSpreadsheet class="size-4" />
                        Montos
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd class="font-medium">{{ formatoMoneda(cotizacion.subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">IVA</dt>
                            <dd class="font-medium">{{ formatoMoneda(cotizacion.iva) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Total</dt>
                            <dd class="font-medium">{{ formatoMoneda(cotizacion.total) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm md:col-span-2">
                    <div class="mb-4 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Calendar class="size-4" />
                        Fechas
                    </div>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Fecha</dt>
                            <dd class="font-medium">{{ cotizacion.fecha ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Creado</dt>
                            <dd class="font-medium">{{ cotizacion.creado ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="cotizacion.notas" class="rounded-2xl border bg-card p-5 shadow-sm md:col-span-2">
                    <div class="mb-2 text-sm font-medium text-muted-foreground">Notas</div>
                    <p class="text-sm">{{ cotizacion.notas }}</p>
                </div>
            </div>
        </div>

        <Dialog v-model:open="nuevaPartidaDialogOpen">
            <DialogContent>
                <Form
                    v-bind="PartidaController.store.form({ planta: planta.id, proyecto: levantamiento.id, levantamiento: levantamiento.id, cotizacion: cotizacion.id })"
                    reset-on-success
                    :options="{ preserveScroll: true }"
                    @success="nuevaPartidaDialogOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <DialogHeader>
                        <DialogTitle>Nueva partida</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="p-descripcion">Descripción</Label>
                        <Input id="p-descripcion" name="descripcion" />
                        <InputError :message="errors.descripcion" />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="p-cantidad">Cantidad</Label>
                            <Input id="p-cantidad" name="cantidad" type="number" step="0.01" />
                            <InputError :message="errors.cantidad" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="p-unidad">Unidad</Label>
                            <Input id="p-unidad" name="unidad" placeholder="pza, m2, etc." />
                        </div>
                        <div class="grid gap-2">
                            <Label for="p-precio">Precio Unitario</Label>
                            <Input id="p-precio" name="precio_unitario" type="number" step="0.01" />
                            <InputError :message="errors.precio_unitario" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="p-costo-hora">Costo por Hora (estimado)</Label>
                        <Input id="p-costo-hora" name="costo_hora" type="number" step="0.01" placeholder="opcional" />
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

        <div class="mt-6 rounded-2xl border bg-card p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-medium text-muted-foreground">Partidas ({{ partidas?.length || 0 }})</p>
                <div class="flex items-center gap-2">
                    <a :href="PartidaController.plantilla({ planta: planta.id, proyecto: levantamiento.id, levantamiento: levantamiento.id, cotizacion: cotizacion.id }).url">
                        <Button variant="outline" size="sm">
                            <Download class="mr-2 size-4" />
                            Plantilla
                        </Button>
                    </a>
                    <label class="cursor-pointer">
                        <Button variant="outline" size="sm" as="span">
                            <Upload class="mr-2 size-4" />
                            Importar Excel
                        </Button>
                        <input
                            ref="archivoPartidasInput"
                            type="file"
                            accept=".xlsx,.xls"
                            class="hidden"
                            @change="subirPlantillaPartidas"
                        />
                    </label>
                    <Button size="sm" @click="nuevaPartidaDialogOpen = true">
                        <Plus class="mr-2 size-4" />
                        Nueva
                    </Button>
                </div>
            </div>

            <div v-if="partidas?.length" class="overflow-hidden rounded-xl border">
                <div class="grid grid-cols-[50px_1fr_90px_80px_110px_110px] gap-2 bg-muted/50 px-4 py-2 text-xs font-medium text-muted-foreground">
                    <span>#</span>
                    <span>Descripción</span>
                    <span>Cant.</span>
                    <span>Unidad</span>
                    <span class="text-right">P. Unit.</span>
                    <span class="text-right">Importe</span>
                </div>
                <div
                    v-for="partida in partidas"
                    :key="partida.id"
                    class="grid grid-cols-[50px_1fr_90px_80px_110px_110px] items-center gap-2 border-t px-4 py-3 text-sm"
                >
                    <span class="text-muted-foreground">{{ partida.numeroPartida }}</span>
                    <span class="truncate">{{ partida.descripcion }}</span>
                    <span>{{ partida.cantidad }}</span>
                    <span class="text-muted-foreground">{{ partida.unidad ?? '—' }}</span>
                    <span class="text-right">{{ formatoMonedaPartida(partida.precioUnitario) }}</span>
                    <span class="text-right font-medium">{{ formatoMonedaPartida(partida.importe) }}</span>
                </div>
            </div>
            <p v-else class="py-8 text-center text-sm text-muted-foreground">Aún no hay partidas.</p>
        </div>
    </PageLayout>
</template>
