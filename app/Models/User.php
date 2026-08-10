<?php

namespace App\Models;

use App\Concerns\HasBitmaskAuthorization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasBitmaskAuthorization;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'firma_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function plantasAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(Planta::class, 'planta_usuario', 'usuario_id', 'planta_id')
            ->withTimestamps();
    }

    public function proyectosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_usuario', 'usuario_id', 'proyecto_id')
            ->withTimestamps();
    }
}
