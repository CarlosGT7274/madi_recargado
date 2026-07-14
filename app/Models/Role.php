<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /**
     * Mapa en memoria recurso_id => permisos (bitmask) para esta instancia
     * de rol. Se resuelve una sola vez, no una query por cada chequeo.
     */
    protected ?Collection $permisosMapa = null;

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(Recurso::class, 'roles_recursos', 'rol_id', 'recurso_id')
            ->withPivot('permisos')
            ->withTimestamps();
    }

    protected function permisosMapa(): Collection
    {
        return $this->permisosMapa ??= $this->recursos()
            ->get()
            ->mapWithKeys(fn (Recurso $recurso): array => [
                $recurso->id => (int) $recurso->pivot->permisos,
            ]);
    }

    /**
     * Bitmask efectivo para un recurso, subiendo por `padre_id` hasta
     * encontrar un grant explícito. 0 si no hay ninguno en toda la rama.
     */
    public function permisosPara(Recurso $recurso): int
    {
        $mapa = $this->permisosMapa();
        $actual = $recurso;

        while ($actual !== null) {
            if ($mapa->has($actual->id)) {
                return $mapa->get($actual->id);
            }

            $actual = $actual->padre;
        }

        return 0;
    }

    public function tienePermiso(Recurso $recurso, int $accion): bool
    {
        return ($this->permisosPara($recurso) & $accion) === $accion;
    }

    /**
     * recurso_id => permisos, expuesto para compartir con el frontend
     * (Inertia) y hacer gating de UI sin ida y vuelta al servidor.
     *
     * @return array<int, int>
     */
    public function mapaPermisos(): array
    {
        return $this->permisosMapa()->all();
    }

    public function otorgar(Recurso $recurso, int $permisos): void
    {
        $this->recursos()->syncWithoutDetaching([
            $recurso->id => ['permisos' => $permisos],
        ]);

        $this->permisosMapa = null;
    }

    public function revocar(Recurso $recurso): void
    {
        $this->recursos()->detach($recurso->id);

        $this->permisosMapa = null;
    }
}
