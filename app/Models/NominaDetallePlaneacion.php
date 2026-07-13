<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominaDetallePlaneacion extends Model
{
    protected $table = 'nomina_detalle_planeaciones';
    public $timestamps = false;

    protected $fillable = [
        'nomina_id',
        'planeacion_id',
        'asignacion_id',
        'horas_trabajadas',
        'precio_hora',
        'monto_calculado',
        'planta_id',
        'proyecto_id',
        'cotizacion_id',
        'dia_semana'
    ];

    protected $casts = [
        'horas_trabajadas' => 'decimal:2',
            'precio_hora' => 'decimal:2',
            'monto_calculado' => 'decimal:2'
    ];

    public function nomina()
    {
        return $this->belongsTo(Nomina::class, 'nomina_id');
    }
    public function planeacion()
    {
        return $this->belongsTo(Planeacion::class, 'planeacion_id');
    }
    public function asignacion()
    {
        return $this->belongsTo(PlaneacionAsignacion::class, 'asignacion_id');
    }
}
