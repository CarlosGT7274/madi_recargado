export const ACCIONES = [
    { bit: 1, label: 'Ver' },
    { bit: 2, label: 'Crear' },
    { bit: 4, label: 'Editar' },
    { bit: 8, label: 'Eliminar' },
] as const;

export function tieneBit(mascara: number, bit: number): boolean {
    return (mascara & bit) === bit;
}

export function alternarBit(mascara: number, bit: number, activo: boolean): number {
    return activo ? mascara | bit : mascara & ~bit;
}
