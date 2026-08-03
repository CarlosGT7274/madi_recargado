<?php

namespace App\Imports;

use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Models\Proyecto;
use App\Support\Ingenierias\LevantamientoPlantillaColumnas;
use App\Support\Ingenierias\LevantamientoRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class LevantamientosImport implements ToCollection
{
    /** @var array<int, array<int, string>> */
    private array $errores = [];

    private int $creados = 0;

    public function __construct(
        private readonly Proyecto $proyecto,
        private readonly LevantamientosAction $action,
    ) {}

    public function collection(Collection $filas): void
    {
        $columnas = LevantamientoPlantillaColumnas::COLUMNAS;

        // Cuántos registros (columnas B..?) trae realmente el archivo subido
        $totalRegistros = $filas->reduce(fn (int $max, Collection $fila) => max($max, $fila->count() - 1), 0);

        for ($reg = 1; $reg <= $totalRegistros; $reg++) {
            $data = [];
            $tieneDatos = false;

            foreach ($columnas as $idx => $columna) {
                $valor = $filas[$idx][$reg] ?? null;
                $vacio = $valor === null || trim((string) $valor) === '';

                if (! $vacio) {
                    $tieneDatos = true;
                }

                $data[$columna['campo']] = match ($columna['tipo']) {
                    'booleano' => $this->aBooleano($valor),
                    default => $vacio ? null : trim((string) $valor),
                };
            }

            if (! $tieneDatos) {
                continue; // "columna" (registro) vacía, se ignora
            }

            $validador = Validator::make($data, LevantamientoRules::rules());

            if ($validador->fails()) {
                $this->errores[$reg] = $validador->errors()->all();

                continue;
            }

            $this->action->create($this->proyecto, $validador->validated());
            $this->creados++;
        }
    }

    private function aBooleano(mixed $valor): bool
    {
        $texto = strtolower(trim((string) $valor));

        return in_array($texto, ['si', 'sí', '1', 'true', 'x'], true);
    }

    /** @return array<int, array<int, string>> */
    public function errores(): array
    {
        return $this->errores;
    }

    public function creados(): int
    {
        return $this->creados;
    }
}
