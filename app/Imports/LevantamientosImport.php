<?php

namespace App\Imports;

use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Models\Levantamiento;
use App\Models\Proyecto;
use App\Support\Ingenierias\LevantamientoPlantillaColumnas;
use App\Support\Ingenierias\LevantamientoRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LevantamientosImport implements ToCollection
{
    /** @var array<int, array<int, string>> */
    private array $errores = [];

    /** @var array<int, Levantamiento> */
    private array $creadosModelos = [];

    public function __construct(
        private readonly Proyecto $proyecto,
        private readonly LevantamientosAction $action,
    ) {}

    public function collection(Collection $filas): void
    {
        $columnas = LevantamientoPlantillaColumnas::COLUMNAS;

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
                    'fecha' => $this->aFecha($valor),
                    default => $vacio ? null : trim((string) $valor),
                };
            }

            if (! $tieneDatos) {
                continue;
            }

            $validador = Validator::make($data, LevantamientoRules::rules(incluirFolio: false));

            if ($validador->fails()) {
                $this->errores[$reg] = $validador->errors()->all();

                continue;
            }

            $this->creadosModelos[] = $this->action->create($this->proyecto, $validador->validated());
        }
    }

    private function aBooleano(mixed $valor): bool
    {
        $texto = strtolower(trim((string) $valor));

        return in_array($texto, ['si', 'sí', '1', 'true', 'x'], true);
    }

    private function aFecha(mixed $valor): ?string
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
            } catch (\Throwable) {
                //
            }
        }

        $texto = trim((string) $valor);

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $texto)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        return $texto;
    }

    /** @return array<int, array<int, string>> */
    public function errores(): array
    {
        return $this->errores;
    }

    public function creados(): int
    {
        return count($this->creadosModelos);
    }

    /** @return array<int, Levantamiento> */
    public function creadosModelos(): array
    {
        return $this->creadosModelos;
    }
}
