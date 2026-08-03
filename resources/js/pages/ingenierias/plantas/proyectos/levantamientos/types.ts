export interface LevantamientoFormData {
    folio: string;
    nombre: string | null;
    cliente: string | null;
    obra: string | null;
    solicitante: string | null;
    fecha_solicitud: string | null;
    usuario_requiriente: string | null;
    correo_usuario: string | null;
    area_trabajo: string | null;
    titulo_cotizacion: string | null;
    medio_solicitud: string | null;
    prioridad: string;
    trabajos_alturas_certificado: boolean;
    trabajos_alturas_notas: string | null;
    espacios_confinados_aplica: boolean;
    espacios_confinados_certificado: boolean;
    espacios_confinados_notas: string | null;
    corte_soldadura_aplica: boolean;
    corte_soldadura_certificado: boolean;
    corte_soldadura_notas: string | null;
    izaje_aplica: boolean;
    izaje_certificado: boolean;
    izaje_notas: string | null;
    apertura_lineas_aplica: boolean;
    apertura_lineas_certificado: boolean;
    apertura_lineas_notas: string | null;
    excavacion_aplica: boolean;
    excavacion_certificado: boolean;
    excavacion_notas: string | null;
    notas_maquinaria: string | null;
    notas_admin: string | null;
    fecha_levantamiento_programada: string | null;
    fecha_envio_cotizacion_programada: string | null;
    fecha_cotizacion_enviada: string | null;
    estatus_admin?: string;
}

export type LevantamientoErrors = Partial<Record<keyof LevantamientoFormData, string>>;
