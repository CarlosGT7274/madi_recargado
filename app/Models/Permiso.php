<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';
    public $timestamps = false;

    protected $fillable = [
        'padre_id',
        'nombre',
        'endpoint',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function padre()
    {
        return $this->belongsTo(Permiso::class, 'padre_id');
    }
}
