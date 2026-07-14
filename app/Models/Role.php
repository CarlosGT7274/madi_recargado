<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'slug',
        'nombre',
        'es_superadmin',
        'activo',
    ];

    protected $casts = [
        'es_superadmin' => 'boolean',
        'activo' => 'boolean',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(Recurso::class, 'role_recurso')
            ->withPivot('permisos')
            ->withTimestamps();
    }

    /**
     * Bitmask que este rol tiene para un recurso dado. 0 si no está asignado.
     */
    public function permisosPara(string $recursoSlug): int
    {
        $recurso = $this->recursos->firstWhere('slug', $recursoSlug);

        return $recurso ? (int) $recurso->pivot->permisos : 0;
    }
}
