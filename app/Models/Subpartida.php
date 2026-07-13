<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subpartida extends Model
{
    protected $table = 'subpartidas';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'partida_id',
        'numero_subpartida',
        'descripcion',
        'cantidad',
        'unidad',
        'precio_unitario',
        'importe'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'importe' => 'decimal:2'
    ];

    public function partida()
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }
}
