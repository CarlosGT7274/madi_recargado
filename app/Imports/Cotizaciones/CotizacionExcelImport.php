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
use Maatwebsite\Excel\Concerns\ToCollection;

class CotizacionExcelImport implements ToCollection
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

    public function collection(Collection $filas): void
    {
        $inicioTabla = $this->localizarEncabezadoTabla($filas);
        $header = $this->leerEncabezado($filas, $inicioTabla);
        $condicionesEntrega = $this->leerCondicionesEntrega($filas);

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

        // Solo se agregan si se encontraron en el Excel — así, si la
        // plantilla no trae "Moneda", por ejemplo, se respeta el default
        // de la columna ('PESOS MXN') en vez de forzar un null.
        $datosPie = array_filter([
            'tiempo_entrega' => $condicionesEntrega['tiempo_entrega'],
            'dias_credito' => $condicionesEntrega['dias_credito'],
            'vigencia_cotizacion' => $condicionesEntrega['vigencia_cotizacion'],
            'moneda' => $this->leerMoneda($filas),
            'notas' => $this->leerNotas($filas),
        ], fn (?string $valor) => $valor !== null);

        $datos = [...$datosBase, ...$datosPie];

        $this->cotizacion = $this->padre instanceof Levantamiento
            ? $this->cotizacionesAction->create($this->padre, $datos)
            : $this->cotizacionesAction->createParaProyecto($this->padre, $datos);

        if ($inicioTabla === null) {
            $this->errores[0] = ['No se encontró la tabla de partidas ("No.") en el archivo. Se creó la cotización sin partidas.'];

            return;
        }

        $padresPorNumero = [];

        for ($i = $inicioTabla + 1; $i < $filas->count(); $i++) {
            $fila = $filas[$i];
            $no = trim((string) ($fila[0] ?? ''));
            $descripcion = trim((string) ($fila[1] ?? ''));

            if ($no === '' && $descripcion === '') {
                continue;
            }

            if (! str_contains($no, '.')) {
                $padre = $this->cotizacion->partidas()->create([
                    'partida_id' => null,
                    'numero_partida' => (int) $no,
                    'descripcion' => $descripcion !== '' ? $descripcion : "Sección {$no}",
                    'cantidad' => 0,
                    'precio_unitario' => 0,
                    'importe' => 0,
                ]);
                $padresPorNumero[(int) $no] = $padre;

                continue;
            }

            [$numPadre, $numHija] = array_pad(array_map('intval', explode('.', $no, 2)), 2, 0);
            $padre = $padresPorNumero[$numPadre] ?? null;

            if (! $padre) {
                $this->errores[$i + 1] = ["La partida \"{$no}\" no tiene una sección \"{$numPadre}\" definida antes."];

                continue;
            }

            $data = [
                'descripcion' => $descripcion,
                'unidad' => trim((string) ($fila[2] ?? '')) ?: null,
                'cantidad' => (float) ($fila[3] ?? 0),
                'precio_unitario' => (float) ($fila[4] ?? 0),
            ];

            $validador = Validator::make($data, [
                'descripcion' => ['required', 'string'],
                'cantidad' => ['required', 'numeric', 'min:0.01'],
                'unidad' => ['nullable', 'string', 'max:50'],
                'precio_unitario' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validador->fails()) {
                $this->errores[$i + 1] = $validador->errors()->all();

                continue;
            }

            $this->partidasAction->create($this->cotizacion, $validador->validated() + [
                'partida_id' => $padre->id,
                'numero_partida' => $numHija,
            ]);
            $this->partidasCreadas++;
        }
    }

    /**
     * Recorre el archivo desde la fila 0 hasta la fila de la tabla ("No."),
     * sin límite fijo de filas. Así no importa cuántas filas de
     * instrucciones traiga la plantilla por delante — antes esto usaba
     * take(10)/take(20), un número fijo que se rompía en cuanto la
     * plantilla cambiaba de tamaño.
     *
     * @return array<string, string|null>
     */
    private function leerEncabezado(Collection $filas, ?int $inicioTabla): array
    {
        $mapa = [
            'para' => null, 'cliente' => null, 'fecha' => null, 'direccion' => null,
            'proveedor' => null, 'vendedor' => null, 'correo_vendedor' => null, 'obra' => null,
        ];

        // Si no encontramos la tabla, revisamos igual todo el archivo por
        // seguridad (mejor leer de más que quedarnos sin nada).
        $limite = $inicioTabla ?? $filas->count();

        for ($i = 0; $i < $limite; $i++) {
            $fila = $filas[$i];
            $etiqueta = $this->normalizarTexto((string) ($fila[0] ?? ''));
            $valor = trim((string) ($fila[1] ?? ''));

            if ($etiqueta === '' || $valor === '') {
                continue;
            }

            match (true) {
                // "correo" antes que "cliente"/"proveedor" no aplica aquí,
                // pero sí importa el orden entre etiquetas parecidas: se
                // revisa "correo vendedor"/"correo del vendedor" antes que
                // nada más ambiguo.
                str_starts_with($etiqueta, 'para') => $mapa['para'] = $valor,
                str_starts_with($etiqueta, 'fecha') => $mapa['fecha'] = $valor,
                str_starts_with($etiqueta, 'cliente') => $mapa['cliente'] = $valor,
                str_starts_with($etiqueta, 'direcci') => $mapa['direccion'] = $valor,
                str_starts_with($etiqueta, 'proveedor') => $mapa['proveedor'] = $valor,
                str_contains($etiqueta, 'correo') => $mapa['correo_vendedor'] = $valor,
                str_starts_with($etiqueta, 'vendedor') => $mapa['vendedor'] = $valor,
                str_starts_with($etiqueta, 'obra') => $mapa['obra'] = $valor,
                default => null,
            };
        }

        return $mapa;
    }

    /**
     * "Tiempo de Entrega / Días de Crédito / Vigencia Cotización" vienen
     * como tres encabezados en una fila y sus valores en la fila
     * siguiente, cada uno alineado bajo su encabezado (ver plantilla:
     * fila con los 3 títulos, fila de abajo con "07 días...", "30 Días",
     * "15 Días"). Se ubican las columnas de los 3 encabezados y se toma,
     * para cada uno, el primer valor no vacío de la fila siguiente dentro
     * del rango de columnas hasta el siguiente encabezado.
     *
     * @return array{tiempo_entrega: ?string, dias_credito: ?string, vigencia_cotizacion: ?string}
     */
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
     * @param  array<string, string>  $etiquetas  clave => etiqueta normalizada a buscar (substring)
     * @return array<int, string> columna => clave, para las etiquetas encontradas en la fila
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

    /** "Moneda: PESOS MXN" — etiqueta y valor en la misma fila. */
    private function leerMoneda(Collection $filas): ?string
    {
        foreach ($filas as $fila) {
            foreach ($fila as $col => $valor) {
                if (str_starts_with($this->normalizarTexto((string) $valor), 'moneda')) {
                    return $this->primerValorEnRango($fila, $col + 1, PHP_INT_MAX);
                }
            }
        }

        return null;
    }

    /**
     * Busca la celda "NOTA:" o "NOTAS:" (CON dos puntos) — a propósito no
     * matchea "NOTA" a secas, porque ese texto también se usa dentro de
     * la tabla de partidas para anotaciones de una línea específica
     * (celda roja resaltada), y no es lo mismo que las notas generales
     * de la cotización.
     */
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
