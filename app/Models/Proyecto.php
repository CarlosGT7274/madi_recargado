<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $table = 'proyectos';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'planta_id',
        'folio',
        'tipo',
        'nombre',
        'descripcion',
        'estado',
        'estado_revision',
        'usuario_id',
    ];

    protected $attributes = [
        'tipo' => 'grande',
        'estado' => 'activo',
    ];

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function levantamientos()
    {
        return $this->hasMany(Levantamiento::class, 'proyecto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Cotizaciones que cuelgan directamente del proyecto (flujo directo,
     * sin Levantamiento). Ver Cotizacion::esDeProyectoDirecto().
     */
    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'proyecto_id');
    }

    /**
     * Alias para route model binding anidado (scopeBindings()): Laravel
     * pluraliza "Cotizacion" con reglas de inglés y busca cotizacions().
     */
    public function cotizacions()
    {
        return $this->cotizaciones();
    }

    /**
     * Árbol de actividades (equivalente a "partidas") del proyecto directo.
     * Vive en planeacion_actividades vía proyecto_id, autorreferenciado con
     * parent_id. No depende de Planeacion.
     */
    public function actividades()
    {
        return $this->hasMany(PlaneacionActividad::class, 'proyecto_id');
    }

    public function actividads()
    {
        return $this->actividades();
    }

    /**
     * Deriva de la misma fuente de verdad que Obra y Cotización:
     * Cotizacion::estaCompletada(). La columna `proyectos.estado`
     * (activo/completado/cancelado) es un campo manual legado que se deja
     * en la BD por ahora, pero ya no se lee para decidir esto — ver
     * ProyectosAction::list()/detail().
     */
    public function estaCompletado(): bool
    {
        if ($this->tipo === 'chico') {
            return $this->cotizaciones()
                ->with('archivos')
                ->get()
                ->contains(fn (Cotizacion $c) => $c->estaCompletada());
        }

        return $this->levantamientos()
            ->with('cotizaciones')
            ->get()
            ->flatMap->cotizaciones
            ->contains(fn (Cotizacion $c) => $c->estaCompletada());
    }
}
