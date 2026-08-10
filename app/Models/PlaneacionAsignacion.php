<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaneacionAsignacion extends Model
{
    protected $table = 'planeacion_asignaciones';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'planeacion_id',
        'partida_id',
        'empleado_id',
        'dia_semana',
        'estado',
        'horas_trabajadas',
        'horas_extra',
    ];

    protected function casts(): array
    {
        return [
            'horas_trabajadas' => 'decimal:2',
            'horas_extra' => 'decimal:2',
        ];
    }

    public function planeacion(): BelongsTo
    {
        return $this->belongsTo(Planeacion::class, 'planeacion_id');
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(PlaneacionIncidencia::class, 'asignacion_id')->latest('fecha_creacion');
    }
}
