<?php

namespace App\Imports\Ingenierias\Insumos;

use App\Models\Cotizacion;
use App\Support\Ingenierias\Insumos\InsumoParser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class InsumosExcelImport implements ToCollection
{
    private int $creados = 0;

    /** @var array<int, array<int, string>> */
    private array $errores = [];

    public function __construct(
        private readonly Cotizacion $cotizacion,
        private readonly InsumoParser $parser,
    ) {}

    public function collection(Collection $filas): void
    {
        $resultado = $this->parser->parsear($filas);
        $this->errores = $resultado->errores;

        foreach ($resultado->filas as $fila) {
            $this->cotizacion->insumos()->create([
                ...$fila,
                'usuario_carga_id' => auth()->id(),
                'fecha_carga' => now(),
                'estatus' => 'pendiente',
                'activo' => true,
            ]);
            $this->creados++;
        }
    }

    public function creados(): int
    {
        return $this->creados;
    }

    /** @return array<int, array<int, string>> */
    public function errores(): array
    {
        return $this->errores;
    }
}
