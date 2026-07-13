<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $table = 'proyectos';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'levantamiento_id',
        'nombre',
        'descripcion',
        'estado',
        'estado_revision',
        'bloqueado',
        'motivo_bloqueo',
        'fecha_bloqueo',
        'usuario_id'
    ];

    protected $casts = [
        'bloqueado' => 'boolean'
    ];

    public function levantamiento()
    {
        return $this->belongsTo(Levantamiento::class, 'levantamiento_id');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
