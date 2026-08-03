<?php

namespace App\Support\Ingenierias;

use Illuminate\Validation\Rule;

class LevantamientoRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?int $ignorarId = null, bool $incluirFolio = true): array
    {
        $reglas = [
            'nombre' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'obra' => ['nullable', 'string', 'max:255'],
            'solicitante' => ['nullable', 'string', 'max:255'],
            'fecha_solicitud' => ['nullable', 'date'],
            'usuario_requiriente' => ['nullable', 'string', 'max:255'],
            'correo_usuario' => ['nullable', 'email', 'max:255'],
            'area_trabajo' => ['nullable', 'string', 'max:255'],
            'titulo_cotizacion' => ['nullable', 'string', 'max:255'],
            'medio_solicitud' => ['nullable', 'string', 'max:100'],
            'prioridad' => ['required', Rule::in(['urgente', 'normal', 'grande_compleja'])],

            'trabajos_alturas_certificado' => ['nullable', 'boolean'],
            'trabajos_alturas_notas' => ['nullable', 'string'],

            'espacios_confinados_aplica' => ['nullable', 'boolean'],
            'espacios_confinados_certificado' => ['nullable', 'boolean'],
            'espacios_confinados_notas' => ['nullable', 'string'],

            'corte_soldadura_aplica' => ['nullable', 'boolean'],
            'corte_soldadura_certificado' => ['nullable', 'boolean'],
            'corte_soldadura_notas' => ['nullable', 'string'],

            'izaje_aplica' => ['nullable', 'boolean'],
            'izaje_certificado' => ['nullable', 'boolean'],
            'izaje_notas' => ['nullable', 'string'],

            'apertura_lineas_aplica' => ['nullable', 'boolean'],
            'apertura_lineas_certificado' => ['nullable', 'boolean'],
            'apertura_lineas_notas' => ['nullable', 'string'],

            'excavacion_aplica' => ['nullable', 'boolean'],
            'excavacion_certificado' => ['nullable', 'boolean'],
            'excavacion_notas' => ['nullable', 'string'],

            'notas_maquinaria' => ['nullable', 'string'],
            'notas_admin' => ['nullable', 'string'],

            'fecha_levantamiento_programada' => ['nullable', 'date'],
            'fecha_envio_cotizacion_programada' => ['nullable', 'date'],
            'fecha_cotizacion_enviada' => ['nullable', 'date'],
        ];

        if ($incluirFolio) {
            $reglas['folio'] = [
                'required', 'string', 'max:100',
                Rule::unique('levantamientos', 'folio')->ignore($ignorarId),
            ];
        }

        return $reglas;
    }
}
