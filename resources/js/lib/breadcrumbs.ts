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

export function breadcrumbsCotizacion(
    planta: PlantaRef,
    proyecto: ProyectoRef,
    levantamiento: LevantamientoRef,
    cotizacion: CotizacionRef,
): BreadcrumbItem[] {
    return [
        ...breadcrumbsLevantamiento(planta, proyecto, levantamiento),
        { title: cotizacion.folio, href: '' },
    ];
}

/**
 * Envuelve un builder de breadcrumbs en el objeto que Inertia espera
 * como `layout` export de una página.
 */
export function pageLayout(build: () => BreadcrumbItem[]) {
    return {
        layout: () => ({ breadcrumbs: build() }),
    };
}
