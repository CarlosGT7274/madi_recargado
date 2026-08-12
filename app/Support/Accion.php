<?php

namespace App\Support;

/**
 * Claves de operación base (RBAC0 / ANSI-INCITS 359). Deben coincidir
 * 1:1 con `operaciones.clave` en el catálogo (ver seeder de
 * RolesPermisosSeeder / migración seed_operaciones_catalogo) — de lo
 * contrario VerificarPermiso::handle() y HasRbacAuthorization::tieneOperacion()
 * nunca encuentran la operación y deniegan todo con 403 aunque el rol
 * tenga el permiso. Accion::ALL NO es una operación real: es un atajo
 * que significa "las cuatro operaciones CRUD base sobre este objeto",
 * resuelto en HasRbacAuthorization::puedePorEndpoint().
 */
final class Accion
{
    public const READ = 'ver';

    public const CREATE = 'crear';

    public const UPDATE = 'editar';

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
