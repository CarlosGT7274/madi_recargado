import PlantaController from '@/actions/App/Http/Controllers/Ingenierias/PlantaController';
import ProyectoController from '@/actions/App/Http/Controllers/Ingenierias/ProyectoController';
import LevantamientoController from '@/actions/App/Http/Controllers/Ingenierias/LevantamientoController';
import CotizacionController from '@/actions/App/Http/Controllers/Ingenierias/CotizacionController';
import type { BreadcrumbItem } from '@/types';

export interface PlantaRef {
    id: number;
    nombre: string;
}

export interface ProyectoRef {
    id: number;
    nombre: string;
}

export interface LevantamientoRef {
    id: number;
    folio: string;
}

export interface CotizacionRef {
    id: number;
    folio: string;
    obra: string | null;
}

export function breadcrumbsPlantas(): BreadcrumbItem[] {
    return [{ title: 'Plantas', href: PlantaController.index() }];
}

export function breadcrumbsPlanta(planta: PlantaRef): BreadcrumbItem[] {
    return [
        ...breadcrumbsPlantas(),
        { title: planta.nombre, href: PlantaController.show(planta.id) },
    ];
}

export function breadcrumbsProyecto(planta: PlantaRef, proyecto: ProyectoRef): BreadcrumbItem[] {
    return [
        ...breadcrumbsPlanta(planta),
        { title: proyecto.nombre, href: ProyectoController.show([planta.id, proyecto.id]) },
    ];
}

export function breadcrumbsLevantamiento(planta: PlantaRef, proyecto: ProyectoRef, levantamiento: LevantamientoRef): BreadcrumbItem[] {
    return [
        ...breadcrumbsProyecto(planta, proyecto),
        {
            title: levantamiento.folio,
            href: LevantamientoController.show({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id }),
        },
    ];
}

export function breadcrumbsLevantamientoNuevo(planta: PlantaRef, proyecto: ProyectoRef): BreadcrumbItem[] {
    return [...breadcrumbsProyecto(planta, proyecto), { title: 'Nuevo Levantamiento', href: '' }];
}

/**
 * "Cotizaciones" ya no tiene una pantalla propia con todas las obras: esa
 * lista vive en el Show del Levantamiento. El breadcrumb regresa ahí.
 */
export function breadcrumbsCotizaciones(planta: PlantaRef, proyecto: ProyectoRef, levantamiento: LevantamientoRef): BreadcrumbItem[] {
    return breadcrumbsLevantamiento(planta, proyecto, levantamiento);
}

export function breadcrumbsObra(planta: PlantaRef, proyecto: ProyectoRef, levantamiento: LevantamientoRef, obra: string): BreadcrumbItem[] {
    return [
        ...breadcrumbsLevantamiento(planta, proyecto, levantamiento),
        {
            title: obra,
            href: CotizacionController.obra({ planta: planta.id, proyecto: proyecto.id, levantamiento: levantamiento.id, obra }),
        },
    ];
}

export function breadcrumbsCotizacion(
    planta: PlantaRef,
    proyecto: ProyectoRef,
    levantamiento: LevantamientoRef,
    cotizacion: CotizacionRef,
): BreadcrumbItem[] {
    return [
        ...breadcrumbsObra(planta, proyecto, levantamiento, cotizacion.obra ?? 'Sin nombre de obra'),
        { title: cotizacion.folio, href: '' },
    ];
}

/**
 * Equivalentes de breadcrumbsObra/breadcrumbsCotizacion para el flujo de
 * Proyecto directo (sin Levantamiento).
 */
export function breadcrumbsObraDirecto(planta: PlantaRef, proyecto: ProyectoRef, obra: string): BreadcrumbItem[] {
    return [
        ...breadcrumbsProyecto(planta, proyecto),
        {
            title: obra,
            href: CotizacionController.obraProyecto({ planta: planta.id, proyecto: proyecto.id, obra }),
        },
    ];
}

export function breadcrumbsCotizacionDirecto(
    planta: PlantaRef,
    proyecto: ProyectoRef,
    cotizacion: CotizacionRef,
): BreadcrumbItem[] {
    return [
        ...breadcrumbsObraDirecto(planta, proyecto, cotizacion.obra ?? 'Sin nombre de obra'),
        { title: cotizacion.folio, href: '' },
    ];
}

export function pageLayout(build: () => BreadcrumbItem[]) {
    return {
        layout: () => ({ breadcrumbs: build() }),
    };
}
