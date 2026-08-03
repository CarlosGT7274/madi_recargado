<?php

namespace App\Imports;

use App\Actions\Ingenierias\Partidas\PartidasAction;
use App\Models\Cotizacion;
use App\Models\Partida;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class PartidasImport implements ToCollection
{
    /** @var array<int, array<int, string>> */
    private array $errores = [];

    private int $creadas = 0;

    /** @var array<int, Partida> numero_partida (nivel raíz) => Partida */
    private array $padresPorNumero = [];

    public function __construct(
        private readonly Cotizacion $cotizacion,
        private readonly PartidasAction $action,
    ) {}

    public function collection(Collection $filas): void
    {
        $inicioTabla = $this->localizarEncabezadoTabla($filas);

        if ($inicioTabla === null) {
            $this->errores[0] = ['No se encontró el encabezado de la tabla (columna "No.") en el archivo.'];

            return;
        }

        for ($i = $inicioTabla + 1; $i < $filas->count(); $i++) {
            $fila = $filas[$i];
            $no = trim((string) ($fila[0] ?? ''));
            $descripcion = trim((string) ($fila[1] ?? ''));

            if ($no === '' && $descripcion === '') {
                continue;
            }

            if (! str_contains($no, '.')) {
                $this->crearPadre($no, $descripcion);

                continue;
            }

            $this->crearHija($i, $no, $descripcion, $fila);
        }
    }

    private function crearPadre(string $no, string $descripcion): void
    {
        $padre = $this->cotizacion->partidas()->create([
            'partida_id' => null,
            'numero_partida' => (int) $no,
            'descripcion' => $descripcion !== '' ? $descripcion : "Sección {$no}",
            'cantidad' => 0,
            'precio_unitario' => 0,
            'importe' => 0,
        ]);

        $this->padresPorNumero[(int) $no] = $padre;
        $this->creadas++;
    }

    /**
     * @param  array<int, mixed>  $fila
     */
    private function crearHija(int $indice, string $no, string $descripcion, array $fila): void
    {
        [$numPadre, $numHija] = array_pad(array_map('intval', explode('.', $no, 2)), 2, 0);
        $padre = $this->padresPorNumero[$numPadre] ?? null;

        if (! $padre) {
            $this->errores[$indice + 1] = ["La partida \"{$no}\" no tiene una sección \"{$numPadre}\" definida antes en el archivo."];

            return;
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
            $this->errores[$indice + 1] = $validador->errors()->all();

            return;
        }

        $this->action->create($this->cotizacion, $validador->validated() + [
            'partida_id' => $padre->id,
            'numero_partida' => $numHija,
        ]);
        $this->creadas++;
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

    /** @return array<int, array<int, string>> */
    public function errores(): array
    {
        return $this->errores;
    }

    public function creadas(): int
    {
        return $this->creadas;
    }
}
