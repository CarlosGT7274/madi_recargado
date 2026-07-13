<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysConfiguracion extends Model
{
    protected $table = 'sys_configuracion';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'categoria',
        'descripcion',
        'editable'
    ];

    protected $casts = [
        'editable' => 'boolean'
    ];

}
