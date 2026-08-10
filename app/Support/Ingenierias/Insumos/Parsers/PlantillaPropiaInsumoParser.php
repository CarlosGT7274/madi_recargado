<?php

namespace App\Support\Ingenierias\Insumos\Parsers;

use App\Support\Ingenierias\Insumos\InsumoParser;
use App\Support\Ingenierias\Insumos\InsumoParseResultado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Parser para la plantilla propia (la que genera InsumoPlantillaExport).
 * Tabla horizontal, encabezado en la fila 1:
 * CODIGO | CONCEPTO | UNIDAD | CATEGORIA | CANTIDAD | PRECIO | IMPORTE
 */
class PlantillaPropiaInsumoParser implements InsumoParser
{
    private const CATEGORIAS_VALIDAS = ['materiales', 'mano_obra', 'maquinaria'];

    public function parsear(Collection $filas): InsumoParseResultado
    {
        $filas = $filas->first();

        $resultado = [];
        $errores = [];

        foreach ($filas->skip(1) as $indice => $fila) {
            $codigo = trim((string) ($fila[0] ?? ''));
            $concepto = trim((string) ($fila[1] ?? ''));

            if ($codigo === '' && $concepto === '') {
                continue; // fila vacía, se ignora
            }

            $data = [
                'codigo' => $codigo,
                'concepto' => $concepto,
                'unidad' => trim((string) ($fila[2] ?? '')),
                'categoria' => strtolower(trim((string) ($fila[3] ?? 'materiales'))),
                'cantidad_presupuestada' => (float) ($fila[4] ?? 0),
                'precio' => ($fila[5] ?? null) !== null && $fila[5] !== '' ? (float) $fila[5] : 0,
                'importe' => (float) ($fila[6] ?? 0),
            ];

            $validador = Validator::make($data, [
                'codigo' => ['required', 'string', 'max:150'],
                'concepto' => ['required', 'string', 'max:500'],
                'unidad' => ['required', 'string', 'max:50'],
                'categoria' => ['required', Rule::in(self::CATEGORIAS_VALIDAS)],
                'cantidad_presupuestada' => ['required', 'numeric', 'min:0'],
                'precio' => ['nullable', 'numeric', 'min:0'],
                'importe' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validador->fails()) {
                $errores[$indice + 2] = $validador->errors()->all();

                continue;
            }

            $resultado[] = $validador->validated();
        }

        return new InsumoParseResultado($resultado, $errores);
    }
}
