<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

class Planeacion extends Model
{
    use HasFactory;

    protected $table = 'planeaciones';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'semana',
        'anio',
        'planta_id',
        'proyecto_id',
        'usuario_id',
        'estado',
        'reportada_nomina',
        'fecha_reporte_nomina',
        'fecha_envio',
        'fecha_aprobacion',
        'fecha_rechazo',
        'aprobador_id',
        'comentarios_aprobacion',
    ];

    protected function casts(): array
    {
        return [
            'reportada_nomina' => 'boolean',
            'fecha_reporte_nomina' => 'datetime',
            'fecha_envio' => 'datetime',
            'fecha_aprobacion' => 'datetime',
            'fecha_rechazo' => 'datetime',
        ];
    }

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(PlaneacionAsignacion::class, 'planeacion_id');
    }

    /**
     * Incidencias de TODAS las asignaciones de esta planeación, sin pasar
     * por una tabla intermedia propia — se apoya en la FK ya existente
     * `planeacion_asignaciones.planeacion_id` y `planeacion_incidencias.asignacion_id`.
     * Habilita `withCount('incidencias')` para el overview del Supervisor
     * sin tener que cargar cada asignación con sus incidencias una por una.
     */
    public function incidencias(): HasManyThrough
    {
        return $this->hasManyThrough(
            PlaneacionIncidencia::class,
            PlaneacionAsignacion::class,
            'planeacion_id',
            'asignacion_id',
        );
    }

    public function fechaInicio(): Carbon
    {
        return Carbon::now()->setISODate($this->anio, $this->semana, 1)->startOfDay();
    }

    public function fechaFin(): Carbon
    {
        return $this->fechaInicio()->copy()->addDays(6)->endOfDay();
    }
}
