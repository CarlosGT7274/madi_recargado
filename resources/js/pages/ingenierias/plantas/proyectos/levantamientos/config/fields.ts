export type CampoTipo = 'text' | 'email' | 'date' | 'textarea' | 'select' | 'boolean';

export interface OpcionSelect {
    value: string;
    label: string;
}

export interface DependsOn {
    key: string;
    equals: boolean;
}

export interface CampoConfig {
    key: string;
    label: string;
    type: CampoTipo;
    required?: boolean;
    placeholder?: string;
    options?: OpcionSelect[];
    dependsOn?: DependsOn;
    colSpan?: 1 | 2;
    /** Si es true, el campo solo se muestra en modo view (nunca editable). */
    soloLectura?: boolean;
    disabled?: boolean;
}

export interface SeccionConfig {
    titulo: string;
    campos: CampoConfig[];
    /** Si es true, esta sección se pinta en un grid de 3 columnas (Trabajos Especiales). */
    gridCompacto?: boolean;
}

const prioridadOptions: OpcionSelect[] = [
    { value: 'normal', label: 'Normal' },
    { value: 'urgente', label: 'Urgente' },
    { value: 'grande_compleja', label: 'Grande / Compleja' },
];

const medioSolicitudOptions: OpcionSelect[] = [
    { value: 'portal', label: 'Portal' },
    { value: 'correo', label: 'Correo' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'telefono', label: 'Teléfono' },
];

function seccionRiesgo(prefijo: string, titulo: string): SeccionConfig {
    return {
        titulo,
        campos: [
            { key: `${prefijo}_aplica`, label: '¿Aplica?', type: 'boolean' },
            {
                key: `${prefijo}_certificado`,
                label: '¿Personal certificado?',
                type: 'boolean',
            },
            {
                key: `${prefijo}_notas`,
                label: 'Notas',
                type: 'textarea',
                colSpan: 2,
            },
        ],
    };
}

export const seccionesLevantamiento: SeccionConfig[] = [
    {
        titulo: 'Identificación',
        campos: [
            // Autogenerado en backend, solo visible en modo view.
            { key: 'folio', label: 'Folio', type: 'text', soloLectura: true },
            { key: 'prioridad', label: 'Prioridad', type: 'select', required: true, options: prioridadOptions },
        ],
    },
    {
        titulo: 'Datos Generales',
        campos: [
            { key: 'solicitante', label: 'Solicitante', type: 'text', required: true, disabled: true },
            { key: 'fecha_solicitud', label: 'Registrado por el Sistema el Día', type: 'date', required: true, disabled: true },
            { key: 'usuario_requiriente', label: 'Nombre del Usuario Requiriente', type: 'text' },
            { key: 'fecha_levantamiento_programada', label: 'Fecha Compromiso', type: 'date' },
            { key: 'correo_usuario', label: 'Correo del Usuario Requiriente', type: 'email' },
            { key: 'fecha_envio_cotizacion_programada', label: 'Fecha Envío Cotización', type: 'date' },
            { key: 'area_trabajo', label: 'Área Trabajo', type: 'text' },
            { key: 'fecha_cotizacion_enviada', label: 'Fecha Cotización Enviada', type: 'date', disabled: true },
            { key: 'titulo_cotizacion', label: 'Título Cotización', type: 'text', colSpan: 2 },
            { key: 'medio_solicitud', label: 'Medio de Solicitud', type: 'select', options: medioSolicitudOptions },
        ],
    },
];

export const seccionesTrabajosEspeciales: SeccionConfig[] = [
    seccionRiesgo('trabajos_alturas', 'Trabajos en Alturas'),
    seccionRiesgo('espacios_confinados', 'Espacios Confinados'),
    seccionRiesgo('corte_soldadura', 'Corte y Soldadura'),
    seccionRiesgo('izaje', 'Izaje'),
    seccionRiesgo('apertura_lineas', 'Apertura de Líneas'),
    seccionRiesgo('excavacion', 'Excavación'),
];

export const seccionMaquinariaNotas: SeccionConfig = {
    titulo: 'Maquinaria y Notas',
    campos: [
        { key: 'notas_maquinaria', label: 'Notas de Maquinaria', type: 'textarea', colSpan: 2 },
        { key: 'notas_admin', label: 'Notas Admin', type: 'textarea', colSpan: 2 },
    ],
};
