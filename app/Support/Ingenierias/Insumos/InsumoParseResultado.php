<?php

namespace App\Support\Ingenierias\Insumos;

final class InsumoParseResultado
{
    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<int, array<int, string>>  $errores
     */
    public function __construct(
        public readonly array $filas,
        public readonly array $errores,
    ) {}
}
