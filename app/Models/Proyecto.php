<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    use HasFactory;

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

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'proyecto_id');
    }

    public function cotizacions()
    {
        return $this->cotizaciones();
    }

    /** TODAS las partidas del proyecto: manuales + las de sus cotizaciones. */
    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class, 'proyecto_id');
    }

    /** Solo las capturadas a mano (sin cotización), para el árbol de "Actividades". */
    public function partidasManuales(): HasMany
    {
        return $this->partidas()->whereNull('cotizacion_id');
    }

    /**
     * Alias para el route model binding anidado (scopeBindings()) de
     * ActividadController: Laravel resuelve /proyectos/{proyecto}/actividades/{actividad}
     * llamando a $proyecto->actividades(), no a partidas(). "Actividad" es
     * solo el nombre de la ruta/URL; el modelo real sigue siendo Partida.
     */
    public function actividades(): HasMany
    {
        return $this->partidas();
    }

    public function planeaciones(): HasMany
    {
        return $this->hasMany(Planeacion::class, 'proyecto_id');
    }

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_usuario', 'proyecto_id', 'usuario_id')
            ->withTimestamps('created_at', 'updated_at');
    }

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
