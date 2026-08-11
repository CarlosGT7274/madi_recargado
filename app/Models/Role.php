<?php

namespace App\Models;

use App\Support\Accion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

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

    /** @var Collection<string, bool>|null clave "{permiso_id}:{operacion_clave}" => true */
    protected ?Collection $operacionesMapa = null;

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roles_usuarios', 'rol_id', 'usuario_id');
    }

    public function permisoOperaciones(): BelongsToMany
    {
        return $this->belongsToMany(PermisoOperacion::class, 'roles_permisos_operaciones', 'rol_id', 'permiso_operacion_id');
    }

    protected function operacionesMapa(): Collection
    {
        return $this->operacionesMapa ??= $this->permisoOperaciones()
            ->with('operacion')
            ->get()
            ->mapWithKeys(fn (PermisoOperacion $po): array => ["{$po->permiso_id}:{$po->operacion->clave}" => true]);
    }

    public function tieneOperacion(Permiso $permiso, string $operacionClave): bool
    {
        $mapa = $this->operacionesMapa();
        $actual = $permiso;

        while ($actual !== null) {
            if ($mapa->has("{$actual->id}:{$operacionClave}")) {
                return true;
            }

            $actual = $actual->padre;
        }

        return false;
    }

    /** Alias de compatibilidad — CompraOrdenAction/CotizacionesAction::administradores() lo llaman así. */
    public function tienePermiso(Permiso $permiso, string $operacionClave): bool
    {
        return $this->tieneOperacion($permiso, $operacionClave);
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
                    || ($url !== null && $this->tieneOperacion($permiso, Accion::READ));

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

    public function otorgarOperacion(Permiso $permiso, string $operacionClave): void
    {
        $permisoOperacion = PermisoOperacion::query()
            ->whereHas('operacion', fn ($q) => $q->where('clave', $operacionClave))
            ->where('permiso_id', $permiso->id)
            ->first();

        if ($permisoOperacion === null) {
            throw new InvalidArgumentException(
                "La operación '{$operacionClave}' no está declarada como aplicable para el permiso '{$permiso->nombre}'."
            );
        }

        $this->permisoOperaciones()->syncWithoutDetaching([$permisoOperacion->id]);
        $this->operacionesMapa = null;
    }

    public function otorgarCrud(Permiso $permiso): void
    {
        foreach (Accion::crud() as $operacionClave) {
            $this->otorgarOperacion($permiso, $operacionClave);
        }
    }

    public function revocarOperacion(Permiso $permiso, string $operacionClave): void
    {
        $permisoOperacion = PermisoOperacion::query()
            ->whereHas('operacion', fn ($q) => $q->where('clave', $operacionClave))
            ->where('permiso_id', $permiso->id)
            ->first();

        if ($permisoOperacion !== null) {
            $this->permisoOperaciones()->detach($permisoOperacion->id);
            $this->operacionesMapa = null;
        }
    }

    public function revocar(Permiso $permiso): void
    {
        $ids = PermisoOperacion::where('permiso_id', $permiso->id)->pluck('id');
        $this->permisoOperaciones()->detach($ids);
        $this->operacionesMapa = null;
    }
}
