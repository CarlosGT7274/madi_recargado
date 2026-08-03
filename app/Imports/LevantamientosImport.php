<?php

namespace App\Imports;

use App\Actions\Ingenierias\Levantamientos\LevantamientosAction;
use App\Models\Planta;
use App\Support\Ingenierias\LevantamientoCampos;
use App\Support\Ingenierias\LevantamientoRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LevantamientosImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array<int, string>> */
    private array $errores = [];

    private int $creados = 0;

    public function __construct(
        private readonly Planta $planta,
        private readonly LevantamientosAction $action,
    ) {}

    public function collection(Collection $filas): void
    {
        $encabezadoAKey = array_flip(
            array_map('strtolower', LevantamientoCampos::mapa())
        );
        $keysBooleanas = LevantamientoCampos::keysBooleanas();

        foreach ($filas as $indice => $fila) {
            $data = [];
            foreach ($fila as $encabezado => $valor) {
                $key = $encabezadoAKey[strtolower((string) $encabezado)] ?? null;
                if ($key === null) {
                    continue;
                }

                if (in_array($key, $keysBooleanas, true)) {
                    $data[$key] = $this->aBooleano($valor);

                    continue;
                }

                $data[$key] = $valor === '' ? null : $valor;
            }

            if (empty(array_filter($data, fn ($v) => $v !== null && $v !== false))) {
                continue; // fila vacía, se ignora
            }

            $validador = Validator::make($data, LevantamientoRules::rules());

            if ($validador->fails()) {
                $this->errores[$indice + 2] = $validador->errors()->all();

                continue;
            }

            $this->action->create($this->planta, $validador->validated());
            $this->creados++;
        }
    }

    private function aBooleano(mixed $valor): bool
    {
        $texto = strtolower(trim((string) $valor));

        return in_array($texto, ['si', 'sí', '1', 'true', 'x'], true);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function errores(): array
    {
        return $this->errores;
    }

    public function creados(): int
    {
        return $this->creados;
    }
}
