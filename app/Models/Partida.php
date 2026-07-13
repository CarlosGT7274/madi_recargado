<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partida extends Model
{
    protected $table = 'partidas';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'cotizacion_id',
        'numero_partida',
        'descripcion',
        'cantidad',
        'unidad',
        'precio_unitario',
        'importe',
        'costo_hora'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'importe' => 'decimal:2',
            'costo_hora' => 'decimal:2'
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }
}
