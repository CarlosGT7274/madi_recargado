<?php

namespace App\Exceptions\Seguridad;

use App\Models\Role;
use RuntimeException;

class RolConUsuariosAsignadosException extends RuntimeException
{
    public function __construct(public readonly Role $role)
    {
        parent::__construct("No puedes eliminar el rol [{$role->nombre}] porque tiene usuarios asignados.");
    }
}
