<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recurso extends Model
{
    protected $fillable = [
        'slug',
        'nombre',
        'modulo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_recurso')
            ->withPivot('permisos')
            ->withTimestamps();
    }
}
