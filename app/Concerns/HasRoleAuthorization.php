<?php

namespace App\Concerns;

use App\Models\Permiso;
use App\Models\Role;
use App\Support\Operacion;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Autorización Core RBAC (RBAC0) a nivel de usuario.
 *
 * Un usuario puede tener uno o varios roles; sus permisos efectivos son la
 * UNIÓN de los permisos de todos sus roles activos (sin "rol activo" ni
 * jerarquías de roles: eso sería RBAC1). Un permiso es siempre
 * `objeto + operación`; ya no existe el bitmask CRUD.
 */
trait HasRoleAuthorization
{
    /**
     * @var array<string, Permiso|null>
     */
    protected array $permisosPorNombreCache = [];

    /**
     * @var array<string, Permiso|null>
     */
    protected array $permisosPorEndpointCache = [];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'rol_id')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, Role>
     */
    protected function rolesActivos(): Collection
    {
        return $this->roles->where('activo', true)->values();
    }

    /**
     * Operaciones efectivas del usuario sobre un objeto: la unión de las
     * operaciones efectivas de cada rol activo (que ya incluye la herencia
     * por el árbol de objetos).
     *
     * @return array<int, string>
     */
    public function operacionesEfectivasPara(Permiso $objeto): array
    {
        $claves = $this->rolesActivos()
            ->flatMap(fn (Role $rol): array => $rol->operacionesEfectivasPara($objeto))
            ->unique()
            ->values()
            ->all();

        return $claves;
    }

    public function tienePermiso(Permiso $objeto, string $operacion): bool
    {
        return in_array($operacion, $this->operacionesEfectivasPara($objeto), true);
    }

    public function puede(string $permisoNombre, string $operacion): bool
    {
        $permiso = $this->permisosPorNombreCache[$permisoNombre]
            ??= Permiso::where('nombre', $permisoNombre)->first();

        return $permiso !== null && $this->tienePermiso($permiso, $operacion);
    }

    /**
     * Resuelve el permiso a partir del endpoint (nombre de ruta) y evalúa
     * la operación. El endpoint completo de cada objeto se deriva de su
     * jerarquía, así que `seguridad.roles` cubre `seguridad.roles.*`. Se
     * toma el prefijo más largo que matchee.
     */
    public function puedePorEndpoint(string $endpoint, string $operacion): bool
    {
        $permiso = $this->permisosPorEndpointCache[$endpoint]
            ??= $this->resolverPermisoPorEndpoint($endpoint);

        return $permiso !== null && $this->tienePermiso($permiso, $operacion);
    }

    protected function resolverPermisoPorEndpoint(string $endpoint): ?Permiso
    {
        return Permiso::with('padre.padre.padre')
            ->get()
            ->map(fn (Permiso $permiso): array => [
                'permiso' => $permiso,
                'completo' => $permiso->endpointCompleto(),
            ])
            ->filter(fn (array $item): bool => $item['completo'] !== null
                && ($endpoint === $item['completo']
                    || str_starts_with($endpoint, $item['completo'].'.')))
            ->sortByDesc(fn (array $item): int => strlen($item['completo']))
            ->first()['permiso'] ?? null;
    }

    public function puedeLeer(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Operacion::LEER);
    }

    public function puedeCrear(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Operacion::CREAR);
    }

    public function puedeActualizar(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Operacion::ACTUALIZAR);
    }

    public function puedeEliminar(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Operacion::ELIMINAR);
    }

    /**
     * Operaciones efectivas por endpoint, consumidas por el frontend.
     * Solo se exponen objetos con endpoint cuyo conjunto efectivo no sea
     * vacío.
     *
     * @return array<string, array<int, string>>
     */
    public function mapaPermisosPorEndpoint(): array
    {
        return Permiso::with('padre.padre.padre')
            ->whereNotNull('endpoint')
            ->get()
            ->mapWithKeys(function (Permiso $permiso): array {
                $endpoint = $permiso->endpointCompleto();

                if ($endpoint === null) {
                    return [];
                }

                $operaciones = $this->operacionesEfectivasPara($permiso);

                return $operaciones === [] ? [] : [$endpoint => $operaciones];
            })
            ->all();
    }

    /**
     * Módulos visibles en el sidebar: objeto activo, con ruta registrada,
     * y con la operación `leer` concedida por alguno de los roles.
     *
     * @return Collection<int, array<string, mixed>>
     */
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
                    || ($url !== null && $this->tienePermiso($permiso, Operacion::LEER));

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
}
