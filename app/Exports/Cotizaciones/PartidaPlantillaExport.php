<?php

namespace App\Exports\Cotizaciones;

use App\Models\Cotizacion;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class PartidaPlantillaExport implements FromArray, WithEvents
{
    /** @var array<int, array<int, mixed>> */
    private array $filas = [];

    /** @var array<int, int> */
    private array $filasNegritas = [];

    /**
     * @param  array<int, array<string, mixed>>  $partidasArbol  resultado de PartidasAction::arbol()
     */
    public function __construct(
        private readonly ?Cotizacion $cotizacion = null,
        private readonly array $partidasArbol = [],
    ) {
        $this->construirFilas();
    }

    private function construirFilas(): void
    {
        $this->filas[] = ['Cliente:', $this->cotizacion?->cliente ?? ''];
        $this->filas[] = ['Dirección:', $this->cotizacion?->direccion ?? ''];
        $this->filas[] = ['Proveedor:', $this->cotizacion?->proveedor ?? ''];
        $this->filas[] = ['Vendedor:', $this->cotizacion?->vendedor ?? ''];
        $this->filas[] = ['Obra:', $this->cotizacion?->obra ?? ''];
        $this->filas[] = [];
        $this->filasNegritas = [1, 2, 3, 4, 5];

        $this->filas[] = ['No.', 'Concepto', 'Unidad', 'Cantidad', 'Precio Unitario'];
        $this->filasNegritas[] = count($this->filas);

        foreach ($this->partidasArbol as $padre) {
            $this->filas[] = [$padre['no'], $padre['descripcion'], null, null, null];
            $this->filasNegritas[] = count($this->filas);

            foreach ($padre['hijas'] as $hija) {
                $this->filas[] = [
                    $hija['no'],
                    $hija['descripcion'],
                    $hija['unidad'],
                    $hija['cantidad'],
                    $hija['precioUnitario'],
                ];
            }
        }
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach ($this->filasNegritas as $fila) {
                    $event->sheet->getDelegate()->getStyle("A{$fila}:E{$fila}")->getFont()->setBold(true);
                }
                foreach (range('A', 'E') as $columna) {
                    $event->sheet->getDelegate()->getColumnDimension($columna)->setAutoSize(true);
                }
            },
        ];
    }
}
