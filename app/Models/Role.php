<?php

namespace App\Models;

use App\Support\Accion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roles_usuarios', 'rol_id', 'usuario_id');
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

    public function tienePermiso(Permiso $permiso, string $accion): bool
    {
        if ($accion === Accion::ALL) {
            foreach (Accion::crud() as $clave) {
                if (! $this->tieneOperacion($permiso, $clave)) {
                    return false;
                }
            }

            return true;
        }

        return $this->tieneOperacion($permiso, $accion);
    }

    /**
     * Puente entre el catálogo de operaciones (clave de texto, ej.
     * 'aprobar') y el bitmask existente en roles_permisos.permisos.
     * Operacion.bit es la fuente de verdad del bit; si la clave no
     * existe en el catálogo, no hay nada que conceder.
     */
    public function tieneOperacion(Permiso $permiso, string $claveOperacion): bool
    {
        $operacion = Operacion::where('clave', $claveOperacion)->where('activo', true)->first();

        if ($operacion === null) {
            return false;
        }

        return ($this->permisosPara($permiso) & $operacion->bit) === $operacion->bit;
    }

    /**
     * @return array<int, int>
     */
    public function mapaPermisos(): array
    {
        return $this->permisosMapa()->all();
    }

    /**
     * @return array<string, int>
     */
    public function mapaPermisosPorEndpoint(): array
    {
        return $this->permisos()
            ->with('padre.padre.padre')
            ->get()
            ->mapWithKeys(function (Permiso $permiso) {
                $endpoint = $permiso->endpointCompleto();

                return $endpoint ? [$endpoint => (int) $permiso->pivot->permisos] : [];
            })->all();
    }

    public function menuVisible(): Collection
    {
        return $this->construirMenu(null);
    }

    protected function construirMenu(?int $padreId, ?string $prefijoPadre = null): Collection
    {
        return Permiso::query()
            ->where('activo', true)
            ->where('padre_id', $padreId)
            ->orderBy('nombre')
            ->get()
            ->map(function (Permiso $permiso) use ($prefijoPadre) {
                $endpointCompleto = Permiso::componerEndpoint($prefijoPadre, $permiso->endpoint);
                $hijos = $this->construirMenu($permiso->id, $endpointCompleto);
                $url = $this->resolverUrl($endpointCompleto);

                $visible = $hijos->isNotEmpty()
                    || ($url !== null && $this->tienePermiso($permiso, Accion::READ));

                if (! $visible) {
                    return null;
                }

                return [
                    'id' => $permiso->id,
                    'nombre' => $permiso->nombre,
                    'endpoint' => $endpointCompleto,
                    'padre_id' => $permiso->padre_id,
                    'url' => $url,
                    'hijos' => $hijos->values(),
                ];
            })
            ->filter()
            ->values();
    }

    protected function resolverUrl(?string $endpoint): ?string
    {
        if ($endpoint === null) {
            return null;
        }

        $nombreRuta = "{$endpoint}.index";

        if (! Route::has($nombreRuta)) {
            return null;
        }

        try {
            return route($nombreRuta);
        } catch (\Throwable) {
            return null;
        }
    }

    public function otorgar(Permiso $permiso, int|string|array $operaciones): void
    {
        $bits = 0;

        if (is_int($operaciones)) {
            $bits = $operaciones;
        } else {
            $claves = is_array($operaciones) ? $operaciones : [$operaciones];

            if (in_array(Accion::ALL, $claves, true)) {
                $claves = array_unique(array_merge($claves, Accion::crud()));
                $claves = array_diff($claves, [Accion::ALL]);
            }

            $bits = Operacion::whereIn('clave', $claves)->where('activo', true)->sum('bit');
        }

        // Recuperar permisos actuales para no sobrescribir, sino acumular
        $actuales = $this->permisosPara($permiso);
        $nuevosBits = $actuales | $bits;

        $this->permisos()->syncWithoutDetaching([
            $permiso->id => ['permisos' => $nuevosBits],
        ]);

        $this->permisosMapa = null;
    }

    public function revocar(Permiso $permiso): void
    {
        $this->permisos()->detach($permiso->id);

        $this->permisosMapa = null;
    }
}
