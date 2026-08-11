<?php

namespace App\Support;

/**
 * Claves de operación base (RBAC0 / ANSI-INCITS 359). Ya no son bits —
 * son las mismas cuatro operaciones CRUD del catálogo `operaciones`,
 * expuestas aquí como constantes para no repetir strings mágicos en
 * rutas y Form Requests. Accion::ALL NO es una operación real: es un
 * atajo que significa "las cuatro operaciones CRUD base sobre este
 * objeto", resuelto en HasRbacAuthorization::puedePorEndpoint().
 */
final class Accion
{
    public const READ = 'ver';

    public const CREATE = 'crear';

    public const UPDATE = 'actualizar';

    public const DELETE = 'eliminar';

    public const ALL = 'administrar';

    /** @return array<int, string> */
    public static function crud(): array
    {
        return [self::READ, self::CREATE, self::UPDATE, self::DELETE];
    }

    public static function esCrud(string $clave): bool
    {
        return in_array($clave, self::crud(), true);
    }
}
