<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraSeguimiento extends Model
{
    protected $table = 'compras_seguimiento';

    protected $fillable = [
        'compra_orden_id',
        'requisicion_material_id',
        'via',
        'cantidad_aprobada'
    ];

    protected $casts = [
        
    ];

    public function compraOrden()
    {
        return $this->belongsTo(CompraOrden::class, 'compra_orden_id');
    }
    public function requisicionMaterial()
    {
        return $this->belongsTo(RequisicionMaterial::class, 'requisicion_material_id');
    }
    public function inventarioItem()
    {
        return $this->belongsTo(InventarioItem::class, 'inventario_item_id');
    }
}
