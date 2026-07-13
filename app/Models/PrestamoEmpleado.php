<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestamoEmpleado extends Model
{
    protected $table = 'prestamos_empleados';

    protected $fillable = [
        'empleado_id',
        'monto_total',
        'monto_quincenal',
        'saldo_pendiente',
        'numero_pagos_total',
        'numero_pagos_realizados',
        'fecha_inicio',
        'fecha_ultimo_pago',
        'estado',
        'descripcion',
        'notas',
        'creado_por_id'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
            'monto_quincenal' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_ultimo_pago' => 'date'
    ];

    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }
}
