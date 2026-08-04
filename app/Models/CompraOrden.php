<?php

namespace App\Models;

use App\Models\Concerns\HasArchivos;
use Illuminate\Database\Eloquent\Model;

class CompraOrden extends Model
{
    use HasArchivos;

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
        'usuario_modificacion_id',
    ];

    protected $casts = [
        'fecha_estimada_entrega' => 'date',
    ];

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function usuarioModificacion()
    {
        return $this->belongsTo(User::class, 'usuario_modificacion_id');
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function pdf(): ?Archivo
    {
        return $this->archivos()->where('tipo_archivo', 'pdf')->latest('fecha_creacion')->first();
    }
}
