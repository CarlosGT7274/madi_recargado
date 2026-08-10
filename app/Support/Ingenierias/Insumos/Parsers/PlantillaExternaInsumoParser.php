<?php

namespace App\Support\Ingenierias\Insumos\Parsers;

use App\Support\Ingenierias\Insumos\InsumoParser;
use App\Support\Ingenierias\Insumos\InsumoParseResultado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Parser para el "Formato Walmart" (nombre genérico, aplica a cualquier
 * cliente que entregue este layout): un libro con UNA hoja por categoría
 * (materiales / mano de obra / maquinaria), bloque de metadatos arriba
 * (Fecha, Concurso No, Obra, Lugar, Ciudad...) y la tabla real más abajo,
 * con encabezado "Código | Concepto | Unidad | Cantidad | Precio | Importe".
 *
 * A diferencia de la plantilla propia, este archivo es "inamovible": no lo
 * controlamos, así que este parser asume que puede venir sucio:
 * - El header no siempre está en la misma fila (se busca dinámicamente).
 * - El nombre de la categoría se deriva del TÍTULO de la hoja, no de una
 *   columna, porque el formato no trae columna de categoría.
 * - Cuando el "Concepto" es largo, Excel/Walmart lo parte en varias filas
 *   seguidas: la fila de continuación trae Código vacío, Cantidad/Precio
 *   en 0, y solo el Concepto con el resto del texto. Ese texto hay que
 *   pegarlo al insumo anterior, NO crear un registro nuevo con él.
 * - Números pueden venir con separadores de miles, símbolos de moneda,
 *   espacios raros, etc. — se limpian con regex antes de castear a float.
 *
 * @implements InsumoParser<Collection<string, Collection<int, array<int, mixed>>>>
 */
class PlantillaExternaInsumoParser implements InsumoParser
{
    private const CATEGORIAS_VALIDAS = ['materiales', 'mano_obra', 'maquinaria'];

    /**
     * Mapea el TÍTULO de la hoja a nuestra categoría interna. Se compara
     * de forma laxa (sin acentos, minúsculas, "contiene") porque el
     * nombre exacto de la hoja puede variar entre archivos del cliente
     * ("e)Listado materiales", "Materiales", "MATERIALES 2024", etc.).
     *
     * @var array<string, string>
     */
    private const PATRONES_HOJA = [
        'materiales' => 'materiales',
        'mano de obra' => 'mano_obra',
        'mano obra' => 'mano_obra',
        'm.o.' => 'mano_obra',
        'm.o' => 'mano_obra',
        'maquinaria' => 'maquinaria',
        'equipo' => 'maquinaria',
    ];

    /**
     * @param  Collection<string, Collection<int, array<int, mixed>>>  $filas  Collection keyed por título de hoja; cada valor es la Collection de filas crudas de esa hoja (arrays 0-indexados por columna).
     */
    public function parsear(Collection $filas): InsumoParseResultado
    {
        $resultado = [];
        $errores = [];

        foreach ($filas as $tituloHoja => $filasHoja) {
            $categoria = $this->categoriaDeHoja((string) $tituloHoja);

            if ($categoria === null) {
                $errores[] = ["La hoja \"{$tituloHoja}\" no coincide con ninguna categoría conocida (materiales, mano de obra, maquinaria) y fue omitida."];

                continue;
            }

            [$filasCategoria, $erroresCategoria] = $this->parsearHoja($filasHoja, $categoria, (string) $tituloHoja);

            array_push($resultado, ...$filasCategoria);
            array_push($errores, ...$erroresCategoria);
        }

        return new InsumoParseResultado($resultado, $errores);
    }

    private function categoriaDeHoja(string $titulo): ?string
    {
        $normalizado = $this->normalizarTexto($titulo);

        foreach (self::PATRONES_HOJA as $patron => $categoria) {
            if (str_contains($normalizado, $this->normalizarTexto($patron))) {
                return $categoria;
            }
        }

        return null;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<int, string>>}
     */
    private function parsearHoja(Collection $filasHoja, string $categoria, string $tituloHoja): array
    {
        $inicioTabla = $this->localizarEncabezado($filasHoja);

        if ($inicioTabla === null) {
            return [[], [["No se encontró el encabezado \"Código | Concepto | Unidad | Cantidad | Precio | Importe\" en la hoja \"{$tituloHoja}\"."]]];
        }

        $resultado = [];
        $errores = [];

        // Buffer del insumo que se está armando. Null cuando no hay uno
        // en construcción (recién procesamos uno, o aún no empezamos).
        $pendiente = null;
        $filaExcelPendiente = null;

        $totalFilas = $filasHoja->count();

        for ($i = $inicioTabla + 1; $i < $totalFilas; $i++) {
            $fila = $filasHoja[$i] ?? [];
            $filaExcel = $i + 1; // +1 porque $filasHoja es 0-indexado

            $codigo = $this->limpiarTexto($fila[0] ?? '');
            $concepto = $this->limpiarTexto($fila[1] ?? '');
            $unidad = $this->limpiarTexto($fila[2] ?? '');
            $cantidad = $this->limpiarNumero($fila[3] ?? null);
            $precio = $this->limpiarNumero($fila[4] ?? null);
            $importe = $this->limpiarNumero($fila[5] ?? null);

            $filaVacia = $codigo === '' && $concepto === '' && $unidad === ''
                && $cantidad === 0.0 && $precio === 0.0 && $importe === 0.0;

            if ($filaVacia) {
                continue;
            }

            // Fila "renglón total" tipo pie de tabla — se ignora, no es insumo.
            if ($codigo === '' && $unidad === '' && str_contains($this->normalizarTexto($concepto), 'total')) {
                continue;
            }

            $esContinuacion = $codigo === '' && $concepto !== '' && $cantidad === 0.0 && $precio === 0.0;

            if ($esContinuacion) {
                if ($pendiente === null) {
                    // Texto huérfano: no hay insumo previo al cual pegarlo.
                    $errores[] = ["Hoja \"{$tituloHoja}\", fila {$filaExcel}: texto de continuación (\"{$concepto}\") sin un insumo previo al cual pertenecer."];

                    continue;
                }

                $pendiente['concepto'] = trim($pendiente['concepto'].' '.$concepto);

                continue;
            }

            // Llegó un insumo nuevo: cerramos el anterior (si había) antes de abrir este.
            if ($pendiente !== null) {
                $this->cerrarPendiente($pendiente, $filaExcelPendiente, $tituloHoja, $resultado, $errores);
            }

            $pendiente = [
                'codigo' => $codigo,
                'concepto' => $concepto,
                'unidad' => $unidad,
                'categoria' => $categoria,
                'cantidad_presupuestada' => $cantidad,
                'precio' => $precio,
                'importe' => $importe,
            ];
            $filaExcelPendiente = $filaExcel;
        }

        if ($pendiente !== null) {
            $this->cerrarPendiente($pendiente, $filaExcelPendiente, $tituloHoja, $resultado, $errores);
        }

        return [$resultado, $errores];
    }

    /**
     * Valida y agrega (o rechaza con error) el insumo acumulado en $pendiente.
     * Recibe los acumuladores por referencia porque cierra el ciclo de vida
     * de UN insumo a la vez, sin necesidad de estado en la clase.
     *
     * @param  array<string, mixed>  $pendiente
     * @param  array<int, array<string, mixed>>  $resultado
     * @param  array<int, array<int, string>>  $errores
     */
    private function cerrarPendiente(array $pendiente, int $filaExcel, string $tituloHoja, array &$resultado, array &$errores): void
    {
        $validador = Validator::make($pendiente, [
            'codigo' => ['required', 'string', 'max:150'],
            'concepto' => ['required', 'string', 'max:500'],
            'unidad' => ['required', 'string', 'max:50'],
            'categoria' => ['required', Rule::in(self::CATEGORIAS_VALIDAS)],
            'cantidad_presupuestada' => ['required', 'numeric', 'min:0'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'importe' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validador->fails()) {
            $errores[] = ["Hoja \"{$tituloHoja}\", fila {$filaExcel}: ".implode(' ', $validador->errors()->all())];

            return;
        }

        $resultado[] = $validador->validated();
    }

    /**
     * Extrae solo dígitos, signo y punto decimal. Convierte "$1,234.50",
     * "1234,50" (formato con coma decimal), " 1 234.50 " y valores vacíos
     * a un float limpio. Cualquier basura no numérica se descarta.
     */
    private function limpiarNumero(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $texto = (string) $valor;

        // Si trae coma Y punto, asumimos la coma como separador de miles
        // (formato "1,234.50") y la quitamos antes de castear.
        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace(',', '', $texto);
        } elseif (str_contains($texto, ',') && ! str_contains($texto, '.')) {
            // Solo coma: la tratamos como decimal ("1234,50" -> "1234.50").
            $texto = str_replace(',', '.', $texto);
        }

        $limpio = preg_replace('/[^0-9\.\-]/', '', $texto) ?? '';

        return $limpio === '' || $limpio === '-' ? 0.0 : (float) $limpio;
    }

    /**
     * Trim + colapso de espacios múltiples/saltos de línea a uno solo.
     */
    private function limpiarTexto(mixed $valor): string
    {
        $texto = trim((string) $valor);

        return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $traduccion = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];

        return strtr($texto, $traduccion);
    }

    /**
     * Busca la fila que contiene el encabezado real de la tabla dentro del
     * bloque de metadatos. No asumimos una fila fija porque el archivo no
     * lo controlamos — buscamos la fila donde la columna A contiene
     * "código"/"codigo" Y la columna B contiene "concepto".
     */
    private function localizarEncabezado(Collection $filasHoja): ?int
    {
        foreach ($filasHoja as $indice => $fila) {
            $colA = $this->normalizarTexto((string) ($fila[0] ?? ''));
            $colB = $this->normalizarTexto((string) ($fila[1] ?? ''));

            if (str_contains($colA, 'codigo') && str_contains($colB, 'concepto')) {
                return $indice;
            }
        }

        return null;
    }
}
