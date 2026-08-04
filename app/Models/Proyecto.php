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

    public function estaCompletado(): bool
    {
        return $this->levantamientos()
            ->with('cotizaciones.ordenCompra', 'cotizaciones.insumos')
            ->get()
            ->flatMap->cotizaciones
            ->contains(fn (Cotizacion $c) => $c->estaCompletada());
    }
}
