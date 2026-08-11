export type OperacionClave = string;

export const Accion = {
    READ: 'ver',
    CREATE: 'crear',
    UPDATE: 'actualizar',
    DELETE: 'eliminar',
    ALL: 'administrar',
} as const;

export const OPERACION_LABELS: Record<string, string> = {
    ver: 'Ver',
    crear: 'Crear',
    actualizar: 'Actualizar',
    eliminar: 'Eliminar',
    enviar: 'Enviar',
    aprobar: 'Aprobar',
    rechazar: 'Rechazar',
    archivar: 'Archivar',
    firmar: 'Firmar',
};

export function labelOperacion(clave: string): string {
    return OPERACION_LABELS[clave] ?? clave.charAt(0).toUpperCase() + clave.slice(1);
}
