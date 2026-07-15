export type PermisoResumen = {
    id: number;
    nombre: string;
    padre_id: number | null;
};

export type RoleResumen = {
    id: number;
    nombre: string;
    activo: boolean;
    usuarios_count: number;
    permisos: Record<number, number>;
};

export type PermisoNodo = {
    id: number;
    nombre: string;
    endpoint: string | null;
    hijos: PermisoNodo[];
};
