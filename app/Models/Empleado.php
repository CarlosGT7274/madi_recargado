<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empleado extends Model
{
    protected $fillable = [
        'nombre',
        'puesto',
        'precio_hora_general',
        'activo',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'precio_hora_general' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
