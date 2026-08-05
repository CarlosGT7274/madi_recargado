<?php

namespace App\Support\Ingenierias;

class LevantamientoPlantillaColumnas
{
    public const OPCIONES_BOOLEANO = ['SI', 'NO'];

    /**
     * Fuente única de verdad para la plantilla vertical de Levantamientos:
     * export (llenar plantilla) e import (leerla) usan este mismo arreglo.
     *
     * NOTA: no incluye 'solicitante' porque el sistema
     * los fija automáticamente (usuario en sesión + hoy) al crear — el
     * importador los sobreescribe sin importar lo que traiga el Excel.
     *
     * @var array<int, array{campo: string, header: string, tipo: string, opciones?: array<int, string>}>
     */
    public const COLUMNAS = [
        ['campo' => 'fecha_solicitud', 'header' => 'Fecha Solicitud (dd/mm/aaaa)', 'tipo' => 'fecha'],
        ['campo' => 'fecha_levantamiento_programada', 'header' => 'Fecha Lev. Prog. (dd/mm/aaaa)', 'tipo' => 'fecha'],
        ['campo' => 'usuario_requiriente', 'header' => 'Usuario Requiriente', 'tipo' => 'texto'],
        ['campo' => 'fecha_envio_cotizacion_programada', 'header' => 'Fecha Envío Prog. (dd/mm/aaaa)', 'tipo' => 'fecha'],
        ['campo' => 'correo_usuario', 'header' => 'Correo', 'tipo' => 'texto'],
        ['campo' => 'area_trabajo', 'header' => 'Área Trabajo', 'tipo' => 'texto'],
        ['campo' => 'titulo_cotizacion', 'header' => 'Título Cotización', 'tipo' => 'texto'],
        ['campo' => 'medio_solicitud', 'header' => 'Medio Solicitud (portal/correo/whatsapp/telefono)', 'tipo' => 'texto', 'opciones' => ['portal', 'correo', 'whatsapp', 'telefono']],
        ['campo' => 'prioridad', 'header' => 'Prioridad (urgente/normal/grande_compleja)', 'tipo' => 'texto', 'opciones' => ['urgente', 'normal', 'grande_compleja']],
        ['campo' => 'trabajos_alturas_aplica', 'header' => 'Trabajos Alturas Aplica (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'trabajos_alturas_certificado', 'header' => 'Trabajos Alturas Certificado (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'trabajos_alturas_notas', 'header' => 'Trabajos Alturas Notas', 'tipo' => 'texto'],
        ['campo' => 'espacios_confinados_aplica', 'header' => 'Espacios Confinados Aplica (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'espacios_confinados_certificado', 'header' => 'Espacios Confinados Certificado (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'espacios_confinados_notas', 'header' => 'Espacios Confinados Notas', 'tipo' => 'texto'],
        ['campo' => 'corte_soldadura_aplica', 'header' => 'Corte y Soldadura Aplica (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'corte_soldadura_certificado', 'header' => 'Corte y Soldadura Certificado (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'corte_soldadura_notas', 'header' => 'Corte y Soldadura Notas', 'tipo' => 'texto'],
        ['campo' => 'izaje_aplica', 'header' => 'Izaje Aplica (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'izaje_certificado', 'header' => 'Izaje Certificado (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'izaje_notas', 'header' => 'Izaje Notas', 'tipo' => 'texto'],
        ['campo' => 'apertura_lineas_aplica', 'header' => 'Apertura Líneas Aplica (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'apertura_lineas_certificado', 'header' => 'Apertura Líneas Certificado (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'apertura_lineas_notas', 'header' => 'Apertura Líneas Notas', 'tipo' => 'texto'],
        ['campo' => 'excavacion_aplica', 'header' => 'Excavación Aplica (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'excavacion_certificado', 'header' => 'Excavación Certificado (SI/NO)', 'tipo' => 'booleano', 'opciones' => self::OPCIONES_BOOLEANO],
        ['campo' => 'excavacion_notas', 'header' => 'Excavación Notas', 'tipo' => 'texto'],
        ['campo' => 'notas_maquinaria', 'header' => 'Notas Maquinaria', 'tipo' => 'texto'],
        ['campo' => 'notas_admin', 'header' => 'Notas Administrativas', 'tipo' => 'texto'],
    ];

    public const REGISTROS_PLANTILLA = 10;
}
