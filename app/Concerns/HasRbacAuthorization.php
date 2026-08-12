<?php

namespace App\Concerns;

use App\Models\Operacion;
use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

trait HasRbacAuthorization
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_usuarios', 'usuario_id', 'rol_id');
    }

    public function puedePorEndpoint(string $endpoint, string $operacion): bool
    {
        $roles = $this->rolesActivos();

        if ($roles->isEmpty()) {
            return false;
        }

        $permiso = $this->resolverPermisoPorEndpoint($endpoint);

        if ($permiso === null) {
            return false;
        }

        $clavesRequeridas = $operacion === Accion::ALL ? Accion::crud() : [$operacion];

        foreach ($clavesRequeridas as $clave) {
            $concedida = $roles->contains(fn (Role $rol) => $rol->tieneOperacion($permiso, $clave));

            if (! $concedida) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function permisosEfectivos(): array
    {
        $roles = $this->rolesActivos();

        if ($roles->isEmpty()) {
            return [];
        }

        $operaciones = Operacion::where('activo', true)->pluck('clave');
        $mapa = [];

        foreach (Permiso::where('activo', true)->get() as $permiso) {
            $endpoint = $permiso->endpointCompleto();

            if ($endpoint === null) {
                continue;
            }

            $concedidas = $operaciones
                ->filter(fn (string $clave) => $roles->contains(fn (Role $rol) => $rol->tieneOperacion($permiso, $clave)))
                ->values()
                ->all();

            if ($concedidas !== []) {
                $mapa[$endpoint] = $concedidas;
            }
        }

        return $mapa;
    }

    /**
     * Sidebar del usuario: unión real de todos sus roles activos, no el
     * árbol de un solo Role. Vive aquí (no en Role::menuVisible, que
     * sigue existiendo para el caso de un rol individual) porque la
     * visibilidad ahora es una propiedad del usuario, no de un rol
     * aislado — usa puedePorEndpoint(), que ya resuelve la unión.
     *
     * @return Collection<int, array{id:int, nombre:string, endpoint:?string, padre_id:?int, url:?string, hijos: Collection}>
     */
    public function menuVisible(): Collection
    {
        return $this->construirMenuUsuario(null);
    }

    private function construirMenuUsuario(?int $padreId, ?string $prefijoPadre = null): Collection
    {
        return Permiso::query()
            ->where('activo', true)
            ->where('padre_id', $padreId)
            ->orderBy('nombre')
            ->get()
            ->map(function (Permiso $permiso) use ($prefijoPadre) {
                $endpointCompleto = Permiso::componerEndpoint($prefijoPadre, $permiso->endpoint);
                $hijos = $this->construirMenuUsuario($permiso->id, $endpointCompleto);
                $url = $this->resolverUrlMenu($endpointCompleto);

                $visible = $hijos->isNotEmpty()
                    || ($url !== null && $this->puedePorEndpoint($endpointCompleto, Accion::READ));

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

    private function resolverUrlMenu(?string $endpoint): ?string
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

    private function rolesActivos(): Collection
    {
        $cache = $this->rolesActivosCache ??= null;

        return $this->rolesActivosCache ??= $this->roles()->where('activo', true)->get();
    }

    private ?Collection $rolesActivosCache = null;

    private function resolverPermisoPorEndpoint(string $endpoint): ?Permiso
    {
        static $cache = [];

        if (array_key_exists($endpoint, $cache)) {
            return $cache[$endpoint];
        }

        $padreId = null;
        $actual = null;

        foreach (explode('.', $endpoint) as $segmento) {
            $hijo = Permiso::query()
                ->where('padre_id', $padreId)
                ->where('endpoint', $segmento)
                ->where('activo', true)
                ->first();

            // Si no existe el segmento hijo en la BD, heredamos el permiso del padre más cercano.
            if ($hijo === null) {
                return $cache[$endpoint] = $actual;
            }

            $actual = $hijo;
            $padreId = $actual->id;
        }

        return $cache[$endpoint] = $actual;
    }
}
