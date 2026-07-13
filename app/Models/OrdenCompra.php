<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'cotizacion_id',
        'folio',
        'proveedor',
        'fecha_orden',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'subtotal',
        'iva',
        'total',
        'estado',
        'notas',
        'usuario_id'
    ];

    protected $casts = [
        'fecha_orden' => 'date',
            'fecha_entrega_estimada' => 'date',
            'fecha_entrega_real' => 'date',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2'
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
