<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaneacionAsignacion extends Model
{
    protected $table = 'planeacion_asignaciones';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'planeacion_id',
        'actividad_id',
        'empleado_id',
        'dia_semana',
        'estado',
        'horas_trabajadas'
    ];

    protected $casts = [
        'horas_trabajadas' => 'decimal:2'
    ];

    public function planeacion()
    {
        return $this->belongsTo(Planeacion::class, 'planeacion_id');
    }
    public function actividad()
    {
        return $this->belongsTo(PlaneacionActividad::class, 'actividad_id');
    }
    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }
}
