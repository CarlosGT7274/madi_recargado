<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'proyecto_id',
        'folio',
        'fecha',
        'para',
        'cliente',
        'direccion',
        'obra',
        'vendedor',
        'proveedor',
        'correo_vendedor',
        'subtotal',
        'iva',
        'total',
        'costo_hora_total',
        'importe_letra',
        'moneda',
        'tiempo_entrega',
        'dias_credito',
        'vigencia_cotizacion',
        'notas',
        'estado',
        'fecha_aprobacion',
        'tiene_insumos',
        'tiene_orden_compra',
        'tiene_partidas',
        'presupuesto_consumido',
        'usuario_id'
    ];

    protected $casts = [
        'fecha' => 'date',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
            'costo_hora_total' => 'decimal:2',
            'fecha_aprobacion' => 'date',
            'tiene_insumos' => 'boolean',
            'tiene_orden_compra' => 'boolean',
            'tiene_partidas' => 'boolean',
            'presupuesto_consumido' => 'decimal:2'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
