<?php

namespace App\Models;

use App\Concerns\HasRbacAuthorization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRbacAuthorization;

    protected $fillable = [
        'name',
        'email',
        'password',
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_usuarios', 'usuario_id', 'rol_id');
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

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'user_id');
    }

    public function compromisos(): HasMany
    {
        return $this->hasMany(CompromisoEntrega::class)->orderBy('fecha');
    }

    /**
     * Resuelve cuál de los compromisos de este usuario aplica para una
     * Planeación concreta, respetando la especificidad (proyecto > planta
     * > general). Si hay más de uno con la misma especificidad (ej. dos
     * compromisos generales), gana el de fecha más próxima aún no
     * vencida; si todos están vencidos, el más reciente vencido.
     */
    public function compromisoVigentePara(Planeacion $planeacion): ?CompromisoEntrega
    {
        $candidatos = $this->compromisos
            ->filter(function (CompromisoEntrega $c) use ($planeacion) {
                if ($c->proyecto_id !== null) {
                    return $c->proyecto_id === $planeacion->proyecto_id;
                }
                if ($c->planta_id !== null) {
                    return $c->planta_id === $planeacion->planta_id;
                }

                return true;
            });

        if ($candidatos->isEmpty()) {
            return null;
        }

        $especificidadMinima = $candidatos->min(fn (CompromisoEntrega $c) => $c->especificidad());
        $delMismoNivel = $candidatos->filter(fn (CompromisoEntrega $c) => $c->especificidad() === $especificidadMinima);

        $vigentes = $delMismoNivel->reject(fn (CompromisoEntrega $c) => $c->vencido());

        return $vigentes->isNotEmpty()
            ? $vigentes->sortBy('fecha')->first()
            : $delMismoNivel->sortByDesc('fecha')->first();
    }
}
