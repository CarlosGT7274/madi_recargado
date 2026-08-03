<?php

namespace App\Exports;

use App\Support\Ingenierias\LevantamientoPlantillaColumnas;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LevantamientoPlantillaExport implements FromArray, WithEvents
{
    public function array(): array
    {
        // Cada fila = un campo. Columna A = encabezado, B..K = registros vacíos.
        return array_map(
            fn (array $columna) => array_merge(
                [$columna['header']],
                array_fill(0, LevantamientoPlantillaColumnas::REGISTROS_PLANTILLA, null),
            ),
            LevantamientoPlantillaColumnas::COLUMNAS,
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnas = LevantamientoPlantillaColumnas::COLUMNAS;
                $totalFilas = count($columnas);
                $registros = LevantamientoPlantillaColumnas::REGISTROS_PLANTILLA;

                // Encabezados en columna A: negrita + fondo gris
                $sheet->getStyle("A1:A{$totalFilas}")->getFont()->setBold(true);
                $sheet->getStyle("A1:A{$totalFilas}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
                $sheet->getStyle("A1:A{$totalFilas}")->getAlignment()
                    ->setHorizontal('left')->setVertical('center');

                // Anchos de columna
                $sheet->getColumnDimension('A')->setWidth(35);
                for ($i = 1; $i <= $registros; $i++) {
                    $sheet->getColumnDimension($this->letra($i))->setWidth(25);
                }

                // Alto de fila
                for ($fila = 1; $fila <= $totalFilas; $fila++) {
                    $sheet->getRowDimension($fila)->setRowHeight(25);
                }

                // Dropdowns para campos con opciones (uno por celda de registro)
                foreach ($columnas as $idx => $columna) {
                    if (empty($columna['opciones'])) {
                        continue;
                    }

                    $fila = $idx + 1;
                    $lista = implode(',', $columna['opciones']);

                    for ($reg = 1; $reg <= $registros; $reg++) {
                        $validacion = $sheet->getCell($this->letra($reg)."{$fila}")->getDataValidation();
                        $validacion->setType(DataValidation::TYPE_LIST);
                        $validacion->setAllowBlank(true);
                        $validacion->setShowDropDown(true);
                        $validacion->setShowErrorMessage(true);
                        $validacion->setErrorTitle('Valor inválido');
                        $validacion->setError("Solo se permite: {$lista}");
                        $validacion->setFormula1('"'.$lista.'"');
                    }
                }

                // Congelar fila 1 y columna A
                $sheet->freezePane('B2');
            },
        ];
    }

    private function letra(int $indiceRegistro): string
    {
        // registro 1 => columna B (índice 2), registro 2 => C, etc.
        return Coordinate::stringFromColumnIndex($indiceRegistro + 1);
    }
}
