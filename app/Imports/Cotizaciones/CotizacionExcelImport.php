<?php

namespace App\Imports\Cotizaciones;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Proyecto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;

class CotizacionExcelImport implements ToCollection, WithColumnLimit
{
    private ?Cotizacion $cotizacion = null;

    /** @var array<int, array<int, string>> */
    private array $errores = [];

    private int $partidasCreadas = 0;

    public function __construct(
        private readonly Levantamiento|Proyecto $padre,
        private readonly CotizacionesAction $cotizacionesAction,
        private readonly PartidasAction $partidasAction,
    ) {}

    public function endColumn(): string
    {
        return 'F';
    }

    /**
     * VALIDACIÓN ESTRUCTURAL DURA. Antes de tocar la base de datos,
     * verifica que el archivo tenga la forma mínima esperada (encabezado
     * "No." + al menos una partida padre válida + al menos un dato de
     * encabezado reconocible). Si algo de esto falla, lanza
     * ValidationException y NO crea absolutamente nada — ni la
     * Cotización ni partidas. Esto reemplaza el comportamiento anterior
     * de "best effort" que creaba una Cotización vacía cuando el Excel
     * no tenía el formato correcto (ej. una plantilla externa distinta a
     * la generada por PartidaPlantillaExport).
     */
    public function collection(Collection $filas): void
    {
        $inicioTabla = $this->localizarEncabezadoTabla($filas);

        if ($inicioTabla === null) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo no tiene el formato esperado: no se encontró el encabezado de tabla "No." en la columna A. '
                    .'Descarga la plantilla oficial y usa esa estructura — no se puede importar un Excel con un layout distinto.',
            ]);
        }

        $header = $this->leerEncabezado($filas, $inicioTabla);

        if ($header['cliente'] === null && $header['obra'] === null) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo no tiene los campos mínimos de encabezado (Cliente u Obra). '
                    .'Verifica que las etiquetas "Cliente:" y "Obra:" estén en la columna A, con su valor en la columna B.',
            ]);
        }

        $condicionesEntrega = $this->leerCondicionesEntrega($filas);
        $finTabla = $this->localizarFinTabla($filas, $inicioTabla);

        $filasPartidas = $this->extraerFilasPartidas($filas, $inicioTabla, $finTabla);

        if ($filasPartidas->isEmpty()) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo no contiene ninguna partida debajo del encabezado "No.". '
                    .'Agrega al menos una sección (ej. "1") y una subpartida (ej. "1.1") antes de importar.',
            ]);
        }

        $erroresEstructura = $this->validarEstructuraPartidas($filasPartidas);

        if ($erroresEstructura !== []) {
            throw ValidationException::withMessages(['archivo' => $erroresEstructura]);
        }

        // ---- A partir de aquí el archivo ya pasó validación estructural: se crea todo ----

        $datosBase = [
            'fecha' => $this->parsearFecha($header['fecha']) ?? now()->toDateString(),
            'para' => $header['para'] ?: null,
            'cliente' => $header['cliente'] ?: null,
            'direccion' => $header['direccion'] ?: null,
            'proveedor' => $header['proveedor'] ?: null,
            'vendedor' => $header['vendedor'] ?: null,
            'correo_vendedor' => $header['correo_vendedor'] ?: null,
            'obra' => $header['obra'] ?: null,
        ];

        $datosPie = array_filter([
            'tiempo_entrega' => $condicionesEntrega['tiempo_entrega'],
            'dias_credito' => $condicionesEntrega['dias_credito'],
            'vigencia_cotizacion' => $condicionesEntrega['vigencia_cotizacion'],
            'notas' => $this->leerNotas($filas),
        ], fn (?string $valor) => $valor !== null);

        $datos = [...$datosBase, ...$datosPie];

        $this->cotizacion = $this->padre instanceof Levantamiento
            ? $this->cotizacionesAction->create($this->padre, $datos)
            : $this->cotizacionesAction->createParaProyecto($this->padre, $datos);

        $padresPorNumero = [];

        foreach ($filasPartidas as $fila) {
            $indiceExcel = $fila['indiceExcel'];
            $no = $fila['no'];
            $descripcion = $fila['descripcion'];

            if (! str_contains($no, '.')) {
                $numPadre = (int) $no;

                $padre = $this->cotizacion->partidas()->create([
                    'partida_id' => null,
                    'proyecto_id' => $this->cotizacion->proyecto_id,
                    'numero_partida' => $numPadre,
                    'descripcion' => $descripcion !== '' ? $descripcion : "Sección {$no}",
                    'cantidad' => 0,
                    'precio_unitario' => 0,
                    'importe' => 0,
                ]);
                $padresPorNumero[$numPadre] = $padre;

                continue;
            }

            [$numPadre, $numHija] = array_pad(array_map('intval', explode('.', $no, 2)), 2, 0);
            $padre = $padresPorNumero[$numPadre];

            $data = [
                'descripcion' => $descripcion,
                'unidad' => $fila['unidad'] ?: null,
                'cantidad' => (float) $fila['cantidad'],
                'precio_unitario' => (float) $fila['precioUnitario'],
            ];

            $this->partidasAction->createSinRecalcular($this->cotizacion, $data + [
                'partida_id' => $padre->id,
                'numero_partida' => $numHija,
            ]);
            $this->partidasCreadas++;
        }

        if ($this->partidasCreadas > 0) {
            $this->partidasAction->recalcularTotales($this->cotizacion);
        }
    }

    /**
     * Extrae y normaliza las filas de la tabla de partidas (sin filas
     * vacías), guardando el número de fila original del Excel para
     * mensajes de error.
     *
     * @return Collection<int, array{indiceExcel:int, no:string, descripcion:string, unidad:string, cantidad:string, precioUnitario:string}>
     */
    private function extraerFilasPartidas(Collection $filas, int $inicioTabla, int $finTabla): Collection
    {
        $resultado = collect();

        for ($i = $inicioTabla + 1; $i < $finTabla; $i++) {
            $fila = $filas[$i];
            $no = trim((string) ($fila[0] ?? ''));
            $descripcion = trim((string) ($fila[1] ?? ''));
            $unidad = trim((string) ($fila[2] ?? ''));
            $cantidad = trim((string) ($fila[3] ?? ''));
            $precioUnitario = trim((string) ($fila[4] ?? ''));

            if ($no === '' && $descripcion === '' && $unidad === '' && $cantidad === '' && $precioUnitario === '') {
                continue;
            }

            $resultado->push([
                'indiceExcel' => $i + 1,
                'no' => $no,
                'descripcion' => $descripcion,
                'unidad' => $unidad,
                'cantidad' => $cantidad,
                'precioUnitario' => $precioUnitario,
            ]);
        }

        return $resultado;
    }

    /**
     * Replica en el servidor las mismas reglas del validador de frontend
     * (useExcelCotizacionValidator.ts): "No." obligatorio y con formato
     * correcto (entero para padres, "N.M" para hijas), secciones
     * definidas antes de sus subpartidas, y cantidad/precio numéricos en
     * subpartidas. El backend NUNCA debe confiar en que el frontend ya
     * validó — el usuario puede subir el archivo directo a la API.
     *
     * @param  Collection<int, array<string, mixed>>  $filasPartidas
     * @return array<int, string>
     */
    private function validarEstructuraPartidas(Collection $filasPartidas): array
    {
        $errores = [];
        $padresDefinidos = [];

        foreach ($filasPartidas as $fila) {
            $no = $fila['no'];
            $excel = $fila['indiceExcel'];

            if ($no === '') {
                $errores[] = "Fila {$excel}: falta el número de partida (columna \"No.\").";

                continue;
            }

            $esPadre = ! str_contains($no, '.');

            if ($esPadre) {
                if (! preg_match('/^[1-9]\d*$/', $no)) {
                    $errores[] = "Fila {$excel}: \"{$no}\" no es un número de sección válido (debe ser un entero como 1, 2, 3).";

                    continue;
                }

                if ((int) $no > 65535) {
                    $errores[] = "Fila {$excel}: el número de sección {$no} excede el límite permitido de 65535.";

                    continue;
                }

                if ($fila['descripcion'] === '') {
                    $errores[] = "Fila {$excel}: la sección \"{$no}\" no tiene descripción.";
                }

                $padresDefinidos[(int) $no] = true;

                continue;
            }

            if (! preg_match('/^[1-9]\d*\.[1-9]\d*$/', $no)) {
                $errores[] = "Fila {$excel}: \"{$no}\" no tiene el formato \"N.M\" esperado para subpartidas (ej. 1.1).";

                continue;
            }

            [$numPadreStr, $numHijaStr] = explode('.', $no, 2);
            $numPadre = (int) $numPadreStr;
            $numHija = (int) $numHijaStr;

            if ($numHija > 65535) {
                $errores[] = "Fila {$excel}: la subpartida \"{$no}\" excede el límite permitido de 65535.";
            }

            if (! isset($padresDefinidos[$numPadre])) {
                $errores[] = "Fila {$excel}: la subpartida \"{$no}\" no tiene una sección \"{$numPadre}\" definida antes.";

                continue;
            }

            $data = [
                'descripcion' => $fila['descripcion'],
                'cantidad' => $fila['cantidad'],
                'precio_unitario' => $fila['precioUnitario'],
                'unidad' => $fila['unidad'] ?: null,
            ];

            $validador = Validator::make($data, [
                'descripcion' => ['required', 'string'],
                'cantidad' => ['required', 'numeric', 'min:0.01'],
                'unidad' => ['nullable', 'string', 'max:50'],
                'precio_unitario' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validador->fails()) {
                foreach ($validador->errors()->all() as $mensaje) {
                    $errores[] = "Fila {$excel} (\"{$no}\"): {$mensaje}";
                }
            }
        }

        return $errores;
    }

    /** @return array<string, string|null> */
    private function leerEncabezado(Collection $filas, ?int $inicioTabla): array
    {
        $mapa = [
            'cliente' => null, 'fecha' => null, 'direccion' => null,
            'proveedor' => null, 'vendedor' => null, 'obra' => null,
            'para' => null, 'correo_vendedor' => null,
        ];

        $limite = $inicioTabla ?? $filas->count();

        for ($i = 0; $i < $limite; $i++) {
            $fila = $filas[$i];
            $etiqueta = $this->normalizarTexto((string) ($fila[0] ?? ''));
            $valor = trim((string) ($fila[1] ?? ''));

            if ($etiqueta === '' || $valor === '') {
                continue;
            }

            match (true) {
                str_starts_with($etiqueta, 'fecha') => $mapa['fecha'] = $valor,
                str_starts_with($etiqueta, 'para') => $mapa['para'] = $valor,
                str_starts_with($etiqueta, 'cliente') => $mapa['cliente'] = $valor,
                str_starts_with($etiqueta, 'direcci') => $mapa['direccion'] = $valor,
                str_starts_with($etiqueta, 'proveedor') => $mapa['proveedor'] = $valor,
                str_starts_with($etiqueta, 'correo vendedor'),
                str_starts_with($etiqueta, 'correo del vendedor') => $mapa['correo_vendedor'] = $valor,
                str_starts_with($etiqueta, 'vendedor') => $mapa['vendedor'] = $valor,
                str_starts_with($etiqueta, 'obra') => $mapa['obra'] = $valor,
                default => null,
            };
        }

        return $mapa;
    }

    /** @return array{tiempo_entrega: ?string, dias_credito: ?string, vigencia_cotizacion: ?string} */
    private function leerCondicionesEntrega(Collection $filas): array
    {
        $vacio = ['tiempo_entrega' => null, 'dias_credito' => null, 'vigencia_cotizacion' => null];

        foreach ($filas as $i => $fila) {
            $columnas = $this->columnasConEtiquetas($fila, [
                'tiempo_entrega' => 'tiempo de entrega',
                'dias_credito' => 'dias de credito',
                'vigencia_cotizacion' => 'vigencia cotizacion',
            ]);

            if (count($columnas) < 3) {
                continue;
            }

            $filaValores = $filas[$i + 1] ?? null;
            if ($filaValores === null) {
                continue;
            }

            ksort($columnas);
            $posiciones = array_keys($columnas);
            $claves = array_values($columnas);

            $resultado = $vacio;
            foreach ($claves as $indice => $clave) {
                $inicio = $posiciones[$indice];
                $fin = $posiciones[$indice + 1] ?? PHP_INT_MAX;
                $resultado[$clave] = $this->primerValorEnRango($filaValores, $inicio, $fin);
            }

            return $resultado;
        }

        return $vacio;
    }

    /**
     * @param  array<string, string>  $etiquetas
     * @return array<int, string>
     */
    private function columnasConEtiquetas(mixed $fila, array $etiquetas): array
    {
        $encontradas = [];

        foreach ($fila as $col => $valor) {
            $normalizado = $this->normalizarTexto((string) $valor);
            if ($normalizado === '') {
                continue;
            }

            foreach ($etiquetas as $clave => $etiqueta) {
                if (str_contains($normalizado, $etiqueta)) {
                    $encontradas[$col] = $clave;
                }
            }
        }

        return $encontradas;
    }

    private function primerValorEnRango(mixed $fila, int $inicio, int $fin): ?string
    {
        foreach ($fila as $col => $valor) {
            if ($col < $inicio || $col >= $fin) {
                continue;
            }

            $texto = trim((string) $valor);
            if ($texto !== '') {
                return $texto;
            }
        }

        return null;
    }

    private function leerNotas(Collection $filas): ?string
    {
        foreach ($filas as $i => $fila) {
            foreach ($fila as $col => $valor) {
                $normalizado = $this->normalizarTexto((string) $valor);

                if ($normalizado !== 'nota:' && $normalizado !== 'notas:') {
                    continue;
                }

                $enMismaFila = $this->primerValorEnRango($fila, $col + 1, PHP_INT_MAX);
                if ($enMismaFila !== null) {
                    return $enMismaFila;
                }

                $filaSiguiente = $filas[$i + 1] ?? null;
                if ($filaSiguiente !== null) {
                    return $this->primerValorEnRango($filaSiguiente, 0, PHP_INT_MAX);
                }
            }
        }

        return null;
    }

    private function parsearFecha(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $traduccion = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];

        return strtr($texto, $traduccion);
    }

    private function localizarEncabezadoTabla(Collection $filas): ?int
    {
        foreach ($filas as $i => $fila) {
            if (strtolower(trim((string) ($fila[0] ?? ''))) === 'no.') {
                return $i;
            }
        }

        return null;
    }

    private function localizarFinTabla(Collection $filas, int $inicioTabla): int
    {
        for ($i = $inicioTabla + 1; $i < $filas->count(); $i++) {
            $fila = $filas[$i];

            $etiquetasCondiciones = $this->columnasConEtiquetas($fila, [
                'tiempo_entrega' => 'tiempo de entrega',
                'dias_credito' => 'dias de credito',
                'vigencia_cotizacion' => 'vigencia cotizacion',
            ]);

            if (count($etiquetasCondiciones) >= 2) {
                return $i;
            }

            $primeraCelda = $this->normalizarTexto((string) ($fila[0] ?? ''));
            if ($primeraCelda === 'nota:' || $primeraCelda === 'notas:') {
                return $i;
            }
        }

        return $filas->count();
    }

    public function cotizacion(): ?Cotizacion
    {
        return $this->cotizacion;
    }

    /** @return array<int, array<int, string>> */
    public function errores(): array
    {
        return $this->errores;
    }

    public function partidasCreadas(): int
    {
        return $this->partidasCreadas;
    }
}
