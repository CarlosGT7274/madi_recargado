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
        'parent_id',
        'proyecto_id',
        'codigo',
        'nombre',
        'dia_semana',
        'notas',
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

    public function padre()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function hijas()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
