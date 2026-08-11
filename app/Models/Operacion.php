<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operacion extends Model
{
    protected $table = 'operaciones';

    public $timestamps = false;

    protected $fillable = [
        'clave',
        'nombre',
        'bit',
        'basica',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'basica' => 'boolean',
        ];
    }
}
