<script setup lang="ts">
import { computed } from 'vue';
import {
    seccionesLevantamiento,
    seccionesTrabajosEspeciales,
    seccionMaquinariaNotas,
    type CampoConfig,
    type SeccionConfig,
} from '../config/fields';
import type { LevantamientoFormData, LevantamientoErrors } from '../types';
import InputError from '@/components/InputError.vue';
import ToggleSiNo from '@/components/ToggleSiNo.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Modo = 'create' | 'view' | 'edit';

const props = defineProps<{
    mode: Modo;
    data: LevantamientoFormData;
    errors?: LevantamientoErrors;
}>();

const emit = defineEmits<{
    (e: 'update', payload: LevantamientoFormData): void;
}>();

const esEditable = computed(() => props.mode === 'create' || props.mode === 'edit');

function valorDe(key: string): unknown {
    return (props.data as Record<string, unknown>)[key];
}

function errorDe(key: string): string | undefined {
    return (props.errors as Record<string, string> | undefined)?.[key];
}

function actualizar(key: string, valor: unknown): void {
    emit('update', { ...props.data, [key]: valor });
}

function seVisible(campo: CampoConfig): boolean {
    // Folio (u otro campo soloLectura) nunca se muestra en modo create,
    // porque aún no existe (se autogenera al guardar).
    if (campo.soloLectura && props.mode === 'create') return false;
    if (!campo.dependsOn) return true;
    return valorDe(campo.dependsOn.key) === campo.dependsOn.equals;
}

function esEditableCampo(campo: CampoConfig): boolean {
    return esEditable.value && !campo.soloLectura && !campo.disabled;
}

function textoPlano(campo: CampoConfig): string {
    const valor = valorDe(campo.key);
    if (campo.type === 'boolean') return valor ? 'Sí' : 'No';
    if (campo.type === 'select') {
        const opcion = campo.options?.find((o) => o.value === valor);
        return opcion?.label ?? (valor as string) ?? '—';
    }
    if (valor === null || valor === undefined || valor === '') return '—';
    return String(valor);
}

const seccionesEstandar: SeccionConfig[] = seccionesLevantamiento;
</script>

<template>
    <div class="space-y-6">
        <!-- Secciones estándar: Identificación + Datos Generales (1 columna, cards apiladas) -->
        <div v-for="seccion in seccionesEstandar" :key="seccion.titulo"
            class="rounded-2xl border bg-card p-5 shadow-sm">
            <p class="mb-4 text-sm font-medium text-muted-foreground">{{ seccion.titulo }}</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <template v-for="campo in seccion.campos" :key="campo.key">
                    <div v-if="seVisible(campo)" class="grid gap-2" :class="campo.colSpan === 2 ? 'sm:col-span-2' : ''">
                        <Label :for="campo.key">
                            {{ campo.label }}
                            <span v-if="campo.required" class="text-destructive">*</span>
                        </Label>

                        <p v-if="mode === 'view' || campo.soloLectura" class="text-sm font-medium">
                            {{ textoPlano(campo) }}
                        </p>

                        <Input v-else-if="campo.disabled" :id="campo.key" :type="campo.type" disabled
                            :model-value="(valorDe(campo.key) as string) ?? ''" />

                        <template v-else>
                            <Select v-if="campo.type === 'select'" :model-value="(valorDe(campo.key) as string) ?? ''"
                                @update:model-value="(v) => actualizar(campo.key, v)">
                                <SelectTrigger :id="campo.key">
                                    <SelectValue :placeholder="campo.placeholder ?? 'Seleccionar'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opcion in campo.options" :key="opcion.value"
                                        :value="opcion.value">
                                        {{ opcion.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Textarea v-else-if="campo.type === 'textarea'" :id="campo.key"
                                :model-value="(valorDe(campo.key) as string) ?? ''"
                                @update:model-value="(v) => actualizar(campo.key, v)" />

                            <Input v-else :id="campo.key" :type="campo.type" :placeholder="campo.placeholder"
                                :model-value="(valorDe(campo.key) as string) ?? ''"
                                @update:model-value="(v) => actualizar(campo.key, v)" />

                            <InputError :message="errorDe(campo.key)" />
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Trabajos Especiales: una sola caja, grid de 3 columnas con 6 mini-tarjetas -->
        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <p class="mb-4 text-sm font-medium text-muted-foreground">Trabajos Especiales</p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="seccion in seccionesTrabajosEspeciales" :key="seccion.titulo" class="rounded-xl border p-4">
                    <p class="mb-3 text-sm font-semibold">{{ seccion.titulo }}</p>

                    <div class="space-y-3">
                        <template v-for="campo in seccion.campos" :key="campo.key">
                            <div v-if="seVisible(campo)">
                                <div v-if="campo.type === 'boolean'" class="flex items-center justify-between gap-2">
                                    <Label :for="campo.key" class="text-xs text-muted-foreground">
                                        {{ campo.label }}
                                    </Label>

                                    <ToggleSiNo v-if="esEditableCampo(campo)" :model-value="Boolean(valorDe(campo.key))"
                                        @update:model-value="(v) => actualizar(campo.key, v)" />
                                    <span v-else class="text-sm font-medium">
                                        {{ valorDe(campo.key) ? 'Sí' : 'No' }}
                                    </span>
                                </div>

                                <div v-else class="grid gap-1">
                                    <Label :for="campo.key" class="text-xs text-muted-foreground">
                                        {{ campo.label }}
                                    </Label>

                                    <p v-if="!esEditableCampo(campo)" class="text-sm">
                                        {{ textoPlano(campo) }}
                                    </p>
                                    <Textarea v-else :id="campo.key" class="min-h-16 text-sm"
                                        :model-value="(valorDe(campo.key) as string) ?? ''"
                                        @update:model-value="(v) => actualizar(campo.key, v)" />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maquinaria y Notas (1 columna) -->
        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <p class="mb-4 text-sm font-medium text-muted-foreground">{{ seccionMaquinariaNotas.titulo }}</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <template v-for="campo in seccionMaquinariaNotas.campos" :key="campo.key">
                    <div class="grid gap-2" :class="campo.colSpan === 2 ? 'sm:col-span-2' : ''">
                        <Label :for="campo.key">{{ campo.label }}</Label>

                        <p v-if="!esEditableCampo(campo)" class="text-sm font-medium">
                            {{ textoPlano(campo) }}
                        </p>
                        <Textarea v-else :id="campo.key" :model-value="(valorDe(campo.key) as string) ?? ''"
                            @update:model-value="(v) => actualizar(campo.key, v)" />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
