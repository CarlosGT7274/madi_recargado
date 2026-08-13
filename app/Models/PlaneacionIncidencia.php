<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaneacionIncidencia extends Model
{
    protected $table = 'planeacion_incidencias';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected $fillable = [
        'asignacion_id',
        'tipo',
        'dia_anterior',
        'dia_nuevo',
        'horas_extra',
        'fecha',
        'notas',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'horas_extra' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(PlaneacionAsignacion::class, 'asignacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
