<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    public $timestamps = false;

    protected $fillable = [
        'mensaje',
        'destino_area',
        'modulo',
        'leida',
        'tipo_entidad',
        'entidad_id',
        'usuario_id',
        'es_general',
        'fecha'
    ];

    protected $casts = [
        'leida' => 'boolean',
            'es_general' => 'boolean'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
