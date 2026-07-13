<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $table = 'insumos';
    public $timestamps = false;

    protected $fillable = [
        'cotizacion_id',
        'codigo',
        'concepto',
        'unidad',
        'categoria',
        'cantidad_presupuestada',
        'cantidad_requisitada',
        'precio',
        'importe',
        'valor_total',
        'estatus',
        'activo',
        'usuario_carga_id',
        'fecha_carga'
    ];

    protected $casts = [
        'cantidad_presupuestada' => 'decimal:2',
            'cantidad_requisitada' => 'decimal:2',
            'precio' => 'decimal:2',
            'importe' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'activo' => 'boolean'
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }
    public function usuarioCarga()
    {
        return $this->belongsTo(User::class, 'usuario_carga_id');
    }
}
