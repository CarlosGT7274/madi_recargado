<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folio extends Model
{
    protected $table = 'folios';
    public $timestamps = false;

    protected $fillable = [
        'modulo',
        'tipo',
        'prefijo',
        'ultimo_numero',
        'fecha_actualizacion'
    ];

    protected $casts = [
        
    ];

}
