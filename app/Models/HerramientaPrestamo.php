<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HerramientaPrestamo extends Model
{
    protected $table = 'herramientas_prestamos';
    public $timestamps = false;

    protected $fillable = [
        'empleado_id',
        'inventario_item_id',
        'requisicion_material_id',
        'cantidad',
        'condicion',
        'estado_asignacion',
        'descripcion',
        'ubicacion',
        'observaciones',
        'fecha_entrega',
        'fecha_devolucion',
        'ultima_actualizacion'
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
            'fecha_devolucion' => 'date'
    ];

    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }
    public function inventarioItem()
    {
        return $this->belongsTo(InventarioItem::class, 'inventario_item_id');
    }
}
