<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisicionMaterial extends Model
{
    protected $table = 'requisicion_materiales';
    public $timestamps = false;

    protected $fillable = [
        'requisicion_id',
        'insumo_id',
        'material',
        'descripcion',
        'cantidad',
        'unidad_medida',
        'urgencia',
        'estado_material',
        'material_distinto_a_autorizado',
        'nota_material_distinto',
        'fecha_llegada',
        'observacion',
        'inventario_actual',
        'cantidad_entregada',
        'sugerencia_compra',
        'importe',
        'enviado_a_compras'
    ];

    protected $casts = [
        'material_distinto_a_autorizado' => 'boolean',
            'fecha_llegada' => 'date',
            'importe' => 'decimal:2',
            'enviado_a_compras' => 'boolean'
    ];

    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }
    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }
}
