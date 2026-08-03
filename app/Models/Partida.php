<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partida extends Model
{
    protected $table = 'partidas';

    protected $fillable = [
        'cotizacion_id',
        'partida_id',
        'numero_partida',
        'descripcion',
        'cantidad',
        'unidad',
        'precio_unitario',
        'importe',
        'costo_hora',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'importe' => 'decimal:2',
            'costo_hora' => 'decimal:2',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(Partida::class, 'partida_id')->orderBy('numero_partida');
    }
}
