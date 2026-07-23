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
     * Resuelve el permiso a partir del nombre de ruta actual. El endpoint
     * completo de cada permiso se deriva de su jerarquía (`endpointCompleto`),
     * de modo que el permiso `Roles` (segmento `roles` bajo `seguridad`)
     * resuelve a `seguridad.roles` y cubre cualquier ruta `seguridad.roles.*`.
     * Se toma siempre el prefijo más largo que matchee, para que un recurso
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
