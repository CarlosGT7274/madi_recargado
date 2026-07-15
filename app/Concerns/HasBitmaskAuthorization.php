<?php

namespace App\Concerns;

use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Se agrega al modelo User del starter kit con: `use HasBitmaskAuthorization;`
 * No reemplaza login/2FA/verificación/sesiones de Laravel — solo añade
 * autorización encima.
 *
 * Dos formas de resolver el permiso, ambas contra la misma tabla `permisos`:
 *   - por `nombre`: para chequeos manuales (Gate, @can, controladores).
 *   - por `endpoint`: para el middleware, que detecta la ruta actual y no
 *     requiere que la escribas a mano en cada definición de ruta.
 */
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
     * Usado por el middleware `permiso`: resuelve el permiso a partir del
     * endpoint (nombre de ruta) actual, sin que la ruta tenga que declarar
     * a qué recurso pertenece.
     */
    public function puedePorEndpoint(string $endpoint, int $accion): bool
    {
        if ($this->rol === null) {
            return false;
        }

        $permiso = $this->permisosPorEndpointCache[$endpoint]
            ??= Permiso::where('endpoint', $endpoint)->first();

        return $permiso !== null && $this->rol->tienePermiso($permiso, $accion);
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
