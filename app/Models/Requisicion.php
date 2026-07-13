<?php

namespace App\Models;

use App\Enums\EstadoRequisicion;
use Illuminate\Database\Eloquent\Model;

class Requisicion extends Model
{
    protected $table = 'requisiciones';
    public $timestamps = false;

    protected $fillable = [
        'folio',
        'proyecto_id',
        'cotizacion_id',
        'empleado_id',
        'estado',
        'origen',
        'observaciones',
        'enviada_a_compras',
        'fecha_solicitud',
        'fecha_actualizacion',
        'fecha_cierre'
    ];

    protected $casts = [
        'enviada_a_compras' => 'boolean',
        'estado' => EstadoRequisicion::class,
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }
    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }
}
