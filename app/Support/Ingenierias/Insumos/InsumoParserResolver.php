<?php

namespace App\Support\Ingenierias\Insumos;

use App\Support\Ingenierias\Insumos\Parsers\PlantillaExternaInsumoParser;
use App\Support\Ingenierias\Insumos\Parsers\PlantillaPropiaInsumoParser;
use InvalidArgumentException;

class InsumoParserResolver
{
    /** @var array<string, class-string<InsumoParser>> */
    private const MAPA = [
        'propia' => PlantillaPropiaInsumoParser::class,
        'externa' => PlantillaExternaInsumoParser::class,
    ];

    public static function resolver(string $tipo): InsumoParser
    {
        $clase = self::MAPA[$tipo] ?? null;

        if ($clase === null) {
            throw new InvalidArgumentException("Tipo de plantilla de insumos no soportado: {$tipo}");
        }

        return app($clase);
    }

    /** @return array<int, string> */
    public static function tiposDisponibles(): array
    {
        return array_keys(self::MAPA);
    }
}
