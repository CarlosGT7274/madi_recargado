<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planeacion extends Model
{
    protected $table = 'planeaciones';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'semana',
        'anio',
        'planta_id',
        'proyecto_id',
        'usuario_id',
        'estado',
        'fecha_envio',
        'fecha_aprobacion',
        'fecha_rechazo',
        'aprobador_id',
        'comentarios_aprobacion'
    ];

    protected $casts = [
        
    ];

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobador_id');
    }
}
