<?php

namespace App\Support\Authorization;

/**
 * Unico bitmask del sistema. Nada de enums por recurso, nada de label()/description().
 * Los recursos (a que se le aplica esto) viven en la tabla `recursos`, no aqui.
 */
final class Permiso
{
    public const READ = 1;
    public const CREATE = 2;
    public const UPDATE = 4;
    public const DELETE = 8;
    public const ALL = 15;

    public static function tiene(int $mascara, int $accion): bool
    {
        return ($mascara & $accion) === $accion;
    }
}
