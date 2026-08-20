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
use Illuminate\Validation\Validator as ValidatorContract;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class CotizacionExcelImport extends DefaultValueBinder implements ToCollection, WithColumnLimit, WithCustomValueBinder
{
    /**
     * Etiquetas legibles para el usuario, usadas en los mensajes de error
     * fila+columna. A propósito NO se usan las claves de columna crudas
     * ("precio_unitario") ni $validador->errors()->all() (que depende de
     * los archivos de traducción de validación) — mismo bug que ya se vio
     * en la importación de Levantamientos ("validation.required" crudo en
     * pantalla). Aquí el texto es literal y no depende de trans().
     */
    private const COLUMNA_LABELS = [
        'descripcion' => 'DESCRIPCIÓN',
        'cantidad' => 'CANTIDAD',
        'unidad' => 'UNIDAD',
        'precio_unitario' => 'PRECIO UNITARIO',
    ];

    private ?Cotizacion $cotizacion = null;

    /** @var array<int, array<int, string>> */
    private array $errores = [];

    private int $partidasCreadas = 0;

    public function __construct(
        private readonly Levantamiento|Proyecto $padre,
        private readonly CotizacionesAction $cotizacionesAction,
        private readonly PartidasAction $partidasAction,
    ) {}

    /**
     * Limita la lectura a las columnas A–F (6 columnas). La plantilla
     * solo usa 5 (No., Descripción, Unidad, Cantidad, P.U.) más una
     * columna extra de margen. Sin este límite, PhpSpreadsheet expande
     * TODAS las columnas que tengan formato/estilo, aunque estén vacías,
     * y eso infla la memoria con miles de objetos Cell innecesarios.
     */
    public function endColumn(): string
    {
        return 'F';
    }

    /**
     * Fuerza la lectura de cada celda como cadena plana. Evita que
     * PhpSpreadsheet cree objetos RichText, DateTime o fórmulas
     * calculadas para cada celda — uno de los mayores consumidores de
     * memoria en archivos .xlsx con formato pesado. El código ya maneja
     * los cast a float/date manualmente.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    public function collection(Collection $filas): void
    {
        $inicioTabla = $this->localizarEncabezadoTabla($filas);
        $header = $this->leerEncabezado($filas, $inicioTabla);
        $condicionesEntrega = $this->leerCondicionesEntrega($filas);
        $finTabla = $inicioTabla !== null
            ? $this->localizarFinTabla($filas, $inicioTabla)
            : $filas->count();

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

        if ($inicioTabla === null) {
            $this->errores[0] = ['No se encontró la tabla de partidas ("No.") en el archivo. Verifica que exista la columna "No." como encabezado.'];

            $this->cotizacion->delete();
            $this->cotizacion = null;

            return;
        }

        $padresPorNumero = [];

        for ($i = $inicioTabla + 1; $i < $finTabla; $i++) {
            $fila = $filas[$i];
            $no = trim((string) ($fila[0] ?? ''));
            $descripcion = trim((string) ($fila[1] ?? ''));

            if ($no === '' && $descripcion === '') {
                continue;
            }

            if (! str_contains($no, '.')) {
                $numPadre = (int) $no;

                if ($numPadre > 65535) {
                    $this->errores[$i + 1] = ["Partida {$no}, columna NO.: El número de sección no puede exceder 65535."];

                    continue;
                }

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

            if ($numHija > 65535) {
                $this->errores[$i + 1] = ["Partida {$no}, columna NO.: El número de subpartida no puede exceder 65535."];

                continue;
            }

            $padre = $padresPorNumero[$numPadre] ?? null;

            if (! $padre) {
                $this->errores[$i + 1] = ["Partida {$no}, columna NO.: La sección {$numPadre} no está definida antes de esta subpartida."];

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
                $this->errores[$i + 1] = $this->mensajesLegibles($no, $validador);

                continue;
            }

            $this->partidasAction->createSinRecalcular($this->cotizacion, $validador->validated() + [
                'partida_id' => $padre->id,
                'numero_partida' => $numHija,
            ]);
            $this->partidasCreadas++;
        }

        if (! empty($this->errores)) {
            // Todo o nada: si alguna fila falló, no dejamos una cotización
            // a medias creada en la base de datos. El usuario corrige TODO
            // el Excel y lo vuelve a subir — igual que un formulario que
            // no guarda nada si un campo falla.
            $this->cotizacion->partidas()->delete();
            $this->cotizacion->delete();
            $this->cotizacion = null;

            return;
        }

        // Recalcular totales UNA SOLA VEZ al final del lote en vez de
        // por cada partida — elimina O(n) queries redundantes.
        if ($this->partidasCreadas > 0) {
            $this->partidasAction->recalcularTotales($this->cotizacion);
        }
    }

    /**
     * Traduce los errores de un `Validator` fallido a mensajes literales
     * "Partida X, columna Y: motivo", sin pasar por trans()/archivos de
     * idioma. `failed()` da, por campo, qué reglas fallaron y con qué
     * parámetros — suficiente para armar el texto nosotros mismos.
     *
     * @return array<int, string>
     */
    private function mensajesLegibles(string $no, ValidatorContract $validador): array
    {
        $mensajes = [];

        foreach ($validador->failed() as $campo => $reglas) {
            $etiqueta = self::COLUMNA_LABELS[$campo] ?? strtoupper($campo);

            foreach ($reglas as $regla => $parametros) {
                $mensajes[] = "Partida {$no}, columna {$etiqueta}: ".$this->mensajeRegla($regla, $parametros);
            }
        }

        return $mensajes;
    }

    /** @param  array<int, mixed>  $parametros */
    private function mensajeRegla(string $regla, array $parametros): string
    {
        return match (strtolower($regla)) {
            'required' => 'Este campo es obligatorio.',
            'numeric' => 'Ingresa un valor numérico válido.',
            'min' => 'El valor debe ser mayor o igual a '.($parametros[0] ?? '0').'.',
            'max' => 'El valor no puede exceder '.($parametros[0] ?? '?').' caracteres.',
            'string' => 'Debe ser un texto válido.',
            default => 'El valor no es válido.',
        };
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

        $formatos = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'm/d/y', 'd/m/y'];

        foreach ($formatos as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $valor);
                if ($fecha !== false) {
                    return $fecha->toDateString();
                }
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

    /**
     * Encuentra dónde termina la tabla de partidas: la primera fila después
     * del encabezado "No." que coincide con el header de condiciones
     * comerciales ("Tiempo de Entrega"/"Días de Crédito"/"Vigencia...") o
     * con la etiqueta "NOTA:"/"NOTAS:". Sin este límite, el loop de partidas
     * seguía leyendo hasta el final del archivo y confundía esas secciones
     * con partidas nuevas (números 0 y 7 inventados a partir de "Tiempo de
     * Entrega" y "07 días...").
     */
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
