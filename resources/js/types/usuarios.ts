export type RolResumen = {
    id: number;
    nombre: string;
};

export interface UsuarioResumen {
    id: number;
    name: string;
    email: string;
    emailVerificado: boolean;
    rolId: number | null; // <- nuevo
    roles: { id: number; nombre: string }[];
}
