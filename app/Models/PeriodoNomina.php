<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoNomina extends Model
{
    protected $table = 'periodos_nomina';

    protected $fillable = [
        'tipo',
        'numero_semana',
        'anio',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'estado',
        'total_empleados',
        'total_horas',
        'total_percepciones',
        'total_deducciones',
        'neto_total',
        'creado_por_id',
        'calculado_por_id',
        'pagado_por_id',
        'fecha_calculo',
        'fecha_pago_real',
        'notas'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_pago' => 'date',
            'total_horas' => 'decimal:2',
            'total_percepciones' => 'decimal:2',
            'total_deducciones' => 'decimal:2',
            'neto_total' => 'decimal:2'
    ];

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
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
