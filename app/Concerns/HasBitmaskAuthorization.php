<?php

namespace App\Concerns;

use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasBitmaskAuthorization
{
    /**
     * @var array<string, Permiso|null>
     */
    protected array $permisosPorNombreCache = [];

    /**
     * @var array<string, Permiso|null>
     */
    protected array $permisosPorEndpointCache = [];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function puede(string $permisoNombre, int $accion): bool
    {
        if ($this->rol === null) {
            return false;
        }

        $permiso = $this->permisosPorNombreCache[$permisoNombre]
            ??= Permiso::where('nombre', $permisoNombre)->first();

        return $permiso !== null && $this->rol->tienePermiso($permiso, $accion);
    }

    /**
     * Resuelve el permiso a partir del nombre de ruta actual. Un permiso
     * cuyo `endpoint` sea `roles` cubre cualquier ruta `roles.*`
     * (roles.index, roles.show, roles.store, etc.), tomando siempre el
     * permiso con el prefijo más largo que matchee, para que un recurso
     * anidado no se confunda con uno más corto.
     */
    public function puedePorEndpoint(string $endpoint, int $accion): bool
    {
        if ($this->rol === null) {
            return false;
        }

        $permiso = $this->permisosPorEndpointCache[$endpoint]
            ??= $this->resolverPermisoPorEndpoint($endpoint);

        return $permiso !== null && $this->rol->tienePermiso($permiso, $accion);
    }

    protected function resolverPermisoPorEndpoint(string $endpoint): ?Permiso
    {
        return Permiso::whereNotNull('endpoint')
            ->get()
            ->filter(fn (Permiso $permiso): bool => $endpoint === $permiso->endpoint
                || str_starts_with($endpoint, $permiso->endpoint.'.'))
            ->sortByDesc(fn (Permiso $permiso): int => strlen($permiso->endpoint))
            ->first();
    }

    public function puedeLeer(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Accion::READ);
    }

    public function puedeCrear(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Accion::CREATE);
    }

    public function puedeActualizar(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Accion::UPDATE);
    }

    public function puedeEliminar(string $permisoNombre): bool
    {
        return $this->puede($permisoNombre, Accion::DELETE);
    }
}
