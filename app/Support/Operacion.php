<?php

namespace App\Support;

/**
 * Claves estables de las OPERACIONES del sistema (Core RBAC).
 *
 * Estas constantes son el identificador que usan rutas, middleware y
 * frontend; el catálogo persistente vive en la tabla `operaciones` y se
 * llena desde aquí en el seeder. CRUD dejó de ser un bitmask central y
 * ahora es, simplemente, cuatro operaciones más del catálogo.
 *
 * Agregar una operación de negocio (p. ej. `firmar`) es añadir una
 * constante aquí, sembrarla, y declararla válida para los objetos donde
 * aplique — sin tocar el esquema.
 */
final class Operacion
{
    public const string LEER = 'leer';

    public const string CREAR = 'crear';

    public const string ACTUALIZAR = 'actualizar';

    public const string ELIMINAR = 'eliminar';

    public const string ENVIAR = 'enviar';

    public const string APROBAR = 'aprobar';

    public const string RECHAZAR = 'rechazar';

    public const string REPORTAR_NOMINA = 'reportar_nomina';

    /**
     * Etiquetas legibles por defecto para el catálogo. El orden define
     * cómo se listan las operaciones (CRUD primero, negocio después).
     *
     * @return array<int, array{clave: string, nombre: string}>
     */
    public static function catalogo(): array
    {
        return [
            ['clave' => self::LEER, 'nombre' => 'Leer'],
            ['clave' => self::CREAR, 'nombre' => 'Crear'],
            ['clave' => self::ACTUALIZAR, 'nombre' => 'Actualizar'],
            ['clave' => self::ELIMINAR, 'nombre' => 'Eliminar'],
            ['clave' => self::ENVIAR, 'nombre' => 'Enviar'],
            ['clave' => self::APROBAR, 'nombre' => 'Aprobar'],
            ['clave' => self::RECHAZAR, 'nombre' => 'Rechazar'],
            ['clave' => self::REPORTAR_NOMINA, 'nombre' => 'Reportar a nómina'],
        ];
    }

    private function __construct()
    {
        //
    }
}
