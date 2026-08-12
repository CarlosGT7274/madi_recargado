export type RolResumen = {
    id: number;
    nombre: string;
};

export type UsuarioResumen = {
    id: number;
    name: string;
    email: string;
    emailVerificado: boolean;
    roles: RolResumen[];
};
