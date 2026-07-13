<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraTracking extends Model
{
    protected $table = 'compras_tracking';
    public $timestamps = false;

    protected $fillable = [
        'compra_orden_id',
        'estado',
        'observacion',
        'ubicacion',
        'usuario',
        'fecha'
    ];

    protected $casts = [
        
    ];

    public function compraOrden()
    {
        return $this->belongsTo(CompraOrden::class, 'compra_orden_id');
    }
}
