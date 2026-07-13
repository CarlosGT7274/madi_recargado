<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'descripcion',
        'ip',
        'fecha_hora'
    ];

    protected $casts = [
        
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
