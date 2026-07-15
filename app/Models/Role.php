<?php

namespace App\Models;

use App\Support\Accion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

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
     * @return array<int, int>
     */
    public function mapaPermisos(): array
    {
        return $this->permisosMapa()->all();
    }

    /**
     * Módulos visibles en el sidebar para este rol: solo permisos activos,
     * con endpoint definido, con ruta registrada, y con bit READ concedido
     * (heredado o directo).
     *
     * @return Collection<int, array{id:int, nombre:string, endpoint:string, padre_id:?int, url:string}>
     */
    public function menuVisible(): Collection
    {
        return $this->construirMenu(null);
    }

    protected function construirMenu(?int $padreId): Collection
    {
        return Permiso::query()
            ->where('activo', true)
            ->where('padre_id', $padreId)
            ->orderBy('nombre')
            ->get()
            ->map(function (Permiso $permiso) {
                $hijos = $this->construirMenu($permiso->id);

                $tieneRuta = $permiso->endpoint !== null && Route::has($permiso->endpoint);
                $visible = $hijos->isNotEmpty()
                    || ($tieneRuta && $this->tienePermiso($permiso, Accion::READ));

                if (! $visible) {
                    return null;
                }

                return [
                    'id' => $permiso->id,
                    'nombre' => $permiso->nombre,
                    'endpoint' => $permiso->endpoint,
                    'padre_id' => $permiso->padre_id,
                    'url' => $tieneRuta ? route($permiso->endpoint) : null,
                    'hijos' => $hijos->values(),
                ];
            })
            ->filter()
            ->values();
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
