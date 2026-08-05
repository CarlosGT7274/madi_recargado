<?php

namespace App\Imports\Cotizaciones;

use App\Actions\Ingenierias\Cotizaciones\CotizacionesAction;
use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
use App\Models\Levantamiento;
use App\Models\Proyecto;
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
        $header = $this->leerEncabezado($filas);

        $datos = [
            'fecha' => now()->toDateString(),
            'cliente' => $header['cliente'] ?: null,
            'direccion' => $header['direccion'] ?: null,
            'proveedor' => $header['proveedor'] ?: null,
            'vendedor' => $header['vendedor'] ?: null,
            'obra' => $header['obra'] ?: null,
        ];

        $this->cotizacion = $this->padre instanceof Levantamiento
            ? $this->cotizacionesAction->create($this->padre, $datos)
            : $this->cotizacionesAction->createParaProyecto($this->padre, $datos);

        $inicioTabla = $this->localizarEncabezadoTabla($filas);

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
     * @return array<string, string|null>
     */
    private function leerEncabezado(Collection $filas): array
    {
        $mapa = ['cliente' => null, 'direccion' => null, 'proveedor' => null, 'vendedor' => null, 'obra' => null];

        foreach ($filas->take(10) as $fila) {
            $etiqueta = strtolower(trim((string) ($fila[0] ?? '')));
            $valor = trim((string) ($fila[1] ?? ''));

            match (true) {
                str_starts_with($etiqueta, 'cliente') => $mapa['cliente'] = $valor,
                str_starts_with($etiqueta, 'direcci') => $mapa['direccion'] = $valor,
                str_starts_with($etiqueta, 'proveedor') => $mapa['proveedor'] = $valor,
                str_starts_with($etiqueta, 'vendedor') => $mapa['vendedor'] = $valor,
                str_starts_with($etiqueta, 'obra') => $mapa['obra'] = $valor,
                default => null,
            };
        }

        return $mapa;
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
