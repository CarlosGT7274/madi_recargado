<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Role extends Model
{
    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Mapa en memoria permiso_id => permisos (bitmask) para esta instancia
     * de rol. Se resuelve una sola vez, no una query por cada chequeo.
     */
    protected ?Collection $permisosMapa = null;

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
            ->withPivot('permisos');
    }

    protected function permisosMapa(): Collection
    {
        return $this->permisosMapa ??= $this->permisos()
            ->get()
            ->mapWithKeys(fn (Permiso $permiso): array => [
                $permiso->id => (int) $permiso->pivot->permisos,
            ]);
    }

    /**
     * Bitmask efectivo para un permiso, subiendo por `padre_id` hasta
     * encontrar un grant explícito. 0 si no hay ninguno en toda la rama.
     */
    public function permisosPara(Permiso $permiso): int
    {
        $mapa = $this->permisosMapa();
        $actual = $permiso;

        while ($actual !== null) {
            if ($mapa->has($actual->id)) {
                return $mapa->get($actual->id);
            }

            $actual = $actual->padre;
        }

        return 0;
    }

    public function tienePermiso(Permiso $permiso, int $accion): bool
    {
        return ($this->permisosPara($permiso) & $accion) === $accion;
    }

    /**
     * permiso_id => permisos, expuesto para compartir con el frontend
     * (Inertia) y hacer gating de UI sin ida y vuelta al servidor.
     *
     * @return array<int, int>
     */
    public function mapaPermisos(): array
    {
        return $this->permisosMapa()->all();
    }

    public function otorgar(Permiso $permiso, int $permisos): void
    {
        $this->permisos()->syncWithoutDetaching([
            $permiso->id => ['permisos' => $permisos],
        ]);

        $this->permisosMapa = null;
    }

    public function revocar(Permiso $permiso): void
    {
        $this->permisos()->detach($permiso->id);

        $this->permisosMapa = null;
    }
}
