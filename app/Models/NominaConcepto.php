<?php

namespace App\Models;

use App\Enums\ConceptoNomina;
use Illuminate\Database\Eloquent\Model;

class NominaConcepto extends Model
{
    protected $table = 'nomina_conceptos';
    public $timestamps = false;

    protected $fillable = [
        'nomina_id',
        'concepto',
        'monto',
        'notas'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'concepto' => ConceptoNomina::class,
    ];

    public function nomina()
    {
        return $this->belongsTo(Nomina::class, 'nomina_id');
    }
}
