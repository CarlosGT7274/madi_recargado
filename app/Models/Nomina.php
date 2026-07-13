<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    protected $table = 'nominas';

    protected $fillable = [
        'periodo_nomina_id',
        'empleado_id',
        'dias_trabajados',
        'horas_normales',
        'horas_extra',
        'precio_hora',
        'pago_horas_normales',
        'pago_horas_extra',
        'total_deducciones',
        'neto_pagar',
        'estado',
        'metodo_pago',
        'referencia_pago',
        'fecha_pago',
        'calculado_por_id',
        'pagado_por_id',
        'notas'
    ];

    protected $casts = [
        'horas_normales' => 'decimal:2',
            'horas_extra' => 'decimal:2',
            'precio_hora' => 'decimal:2',
            'pago_horas_normales' => 'decimal:2',
            'pago_horas_extra' => 'decimal:2',
            'total_deducciones' => 'decimal:2',
            'neto_pagar' => 'decimal:2'
    ];

    public function periodoNomina()
    {
        return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id');
    }
    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }
    public function calculadoPor()
    {
        return $this->belongsTo(User::class, 'calculado_por_id');
    }
    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por_id');
    }
}
