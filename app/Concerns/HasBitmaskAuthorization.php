<?php

namespace App\Concerns;

use App\Models\Role;
use App\Support\Authorization\Permiso;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Se agrega al modelo User del starter kit con: `use HasBitmaskAuthorization;`
 * No reemplaza login/2FA/verificacion/sesiones de Laravel, solo añade autorizacion.
 */
trait HasBitmaskAuthorization
{
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Verdad si el usuario puede realizar $accion (Permiso::READ|CREATE|UPDATE|DELETE)
     * sobre el recurso identificado por su slug (columna `recursos.slug`, dinamico en BD).
     */
    public function puede(string $recursoSlug, int $accion): bool
    {
        if (! $this->role) {
            return false;
        }

        if ($this->role->es_superadmin) {
            return true;
        }

        return Permiso::tiene($this->role->permisosPara($recursoSlug), $accion);
    }

    public function puedeLeer(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Permiso::READ);
    }

    public function puedeCrear(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Permiso::CREATE);
    }

    public function puedeActualizar(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Permiso::UPDATE);
    }

    public function puedeEliminar(string $recursoSlug): bool
    {
        return $this->puede($recursoSlug, Permiso::DELETE);
    }
}
