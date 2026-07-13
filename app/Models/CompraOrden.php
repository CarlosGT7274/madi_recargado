<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraOrden extends Model
{
    protected $table = 'compras_ordenes';

    protected $fillable = [
        'numero_orden',
        'proveedor',
        'proveedor_rfc',
        'estatus_compra',
        'fecha_solicitud_compra',
        'fecha_aprobacion',
        'fecha_estimada_entrega',
        'fecha_entrega',
        'observaciones',
        'usuario_registro_id',
        'usuario_modificacion_id'
    ];

    protected $casts = [
        'fecha_estimada_entrega' => 'date'
    ];

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }
    public function usuarioModificacion()
    {
        return $this->belongsTo(User::class, 'usuario_modificacion_id');
    }
}
