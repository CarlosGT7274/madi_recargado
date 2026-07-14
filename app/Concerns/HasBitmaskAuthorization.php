<?php

namespace App\Concerns;

use App\Models\Recurso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Se agrega al modelo User del starter kit con: `use HasBitmaskAuthorization;`
 * No reemplaza login/2FA/verificación/sesiones de Laravel — solo añade
 * autorización encima. El recurso se identifica por su `slug` (dinámico,
 * en la tabla `recursos`), nunca hardcodeado en un enum.
 */
trait HasBitmaskAuthorization
{
    /**
     * @var array<string, Recurso|null>
     */
    protected array $recursosCache = [];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    /**
     * Verdad si el usuario puede realizar $accion (Accion::READ|CREATE|UPDATE|DELETE)
     * sobre el recurso identificado por su slug.
     */
    public function puede(string $recursoSlug, int $accion): bool
    {
        if ($this->rol === null) {
            return false;
        }

        if ($this->rol->es_superadmin) {
            return true;
        }

        $recurso = $this->recursosCache[$recursoSlug]
            ??= Recurso::where('slug', $recursoSlug)->first();

        return $recurso !== null && $this->rol->tienePermiso($recurso, $accion);
    }

    public function puedeLeer(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Accion::READ);
    }

    public function puedeCrear(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Accion::CREATE);
    }

    public function puedeActualizar(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Accion::UPDATE);
    }

    public function puedeEliminar(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Accion::DELETE);
    }
}
