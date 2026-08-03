<?php

namespace App\Support\Ingenierias;

class LevantamientoCampos
{
    /**
     * Orden y encabezados de la plantilla Excel.
     * Debe reflejar (a mano, por ahora) el mismo set de campos
     * que resources/js/.../config/fields.ts en el frontend.
     *
     * @return array<string, string> key de BD => encabezado visible
     */
    public static function mapa(): array
    {
        return [
            'folio' => 'Folio',
            'nombre' => 'Nombre',
            'cliente' => 'Cliente',
            'obra' => 'Obra',
            'prioridad' => 'Prioridad (urgente/normal/grande_compleja)',
            'solicitante' => 'Solicitante',
            'fecha_solicitud' => 'Fecha Solicitud (YYYY-MM-DD)',
            'usuario_requiriente' => 'Usuario Requiriente',
            'correo_usuario' => 'Correo',
            'area_trabajo' => 'Área Trabajo',
            'titulo_cotizacion' => 'Título Cotización',
            'medio_solicitud' => 'Medio de Solicitud',
            'fecha_levantamiento_programada' => 'Fecha Lev. Programada (YYYY-MM-DD)',
            'fecha_envio_cotizacion_programada' => 'Fecha Envío Cotización Prog. (YYYY-MM-DD)',
            'notas_maquinaria' => 'Notas Maquinaria',
            'notas_admin' => 'Notas Admin',
        ];
    }
}
