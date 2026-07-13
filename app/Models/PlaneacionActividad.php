<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaneacionActividad extends Model
{
    protected $table = 'planeacion_actividades';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'planeacion_id',
        'partida_id',
        'codigo',
        'nombre',
        'dia_semana',
        'notas'
    ];

    protected $casts = [
        
    ];

    public function planeacion()
    {
        return $this->belongsTo(Planeacion::class, 'planeacion_id');
    }
    public function partida()
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }
}
