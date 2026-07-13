<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialPagoPrestamo extends Model
{
    protected $table = 'historial_pagos_prestamos';
    public $timestamps = false;

    protected $fillable = [
        'prestamo_id',
        'nomina_id',
        'monto_pagado',
        'saldo_anterior',
        'saldo_nuevo',
        'fecha_pago'
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
            'saldo_anterior' => 'decimal:2',
            'saldo_nuevo' => 'decimal:2'
    ];

    public function prestamo()
    {
        return $this->belongsTo(PrestamoEmpleado::class, 'prestamo_id');
    }
    public function nomina()
    {
        return $this->belongsTo(Nomina::class, 'nomina_id');
    }
}
