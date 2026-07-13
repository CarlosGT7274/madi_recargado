<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Levantamiento extends Model
{
    protected $table = 'levantamientos';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'planta_id',
        'folio',
        'nombre',
        'cliente',
        'obra',
        'solicitante',
        'fecha_solicitud',
        'usuario_requiriente',
        'correo_usuario',
        'area_trabajo',
        'titulo_cotizacion',
        'trabajos_alturas_certificado',
        'trabajos_alturas_notas',
        'espacios_confinados_aplica',
        'espacios_confinados_certificado',
        'espacios_confinados_notas',
        'corte_soldadura_aplica',
        'corte_soldadura_certificado',
        'corte_soldadura_notas',
        'izaje_aplica',
        'izaje_certificado',
        'izaje_notas',
        'apertura_lineas_aplica',
        'apertura_lineas_certificado',
        'apertura_lineas_notas',
        'excavacion_aplica',
        'excavacion_certificado',
        'excavacion_notas',
        'notas_maquinaria',
        'estatus_admin',
        'medio_solicitud',
        'prioridad',
        'notas_admin',
        'fecha_levantamiento_programada',
        'fecha_envio_cotizacion_programada',
        'fecha_cotizacion_enviada',
        'usuario_id'
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
            'trabajos_alturas_certificado' => 'boolean',
            'espacios_confinados_aplica' => 'boolean',
            'espacios_confinados_certificado' => 'boolean',
            'corte_soldadura_aplica' => 'boolean',
            'corte_soldadura_certificado' => 'boolean',
            'izaje_aplica' => 'boolean',
            'izaje_certificado' => 'boolean',
            'apertura_lineas_aplica' => 'boolean',
            'apertura_lineas_certificado' => 'boolean',
            'excavacion_aplica' => 'boolean',
            'excavacion_certificado' => 'boolean',
            'fecha_levantamiento_programada' => 'date',
            'fecha_envio_cotizacion_programada' => 'date',
            'fecha_cotizacion_enviada' => 'date'
    ];

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
