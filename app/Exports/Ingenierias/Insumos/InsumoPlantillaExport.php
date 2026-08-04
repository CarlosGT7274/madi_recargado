<?php

namespace App\Exports\Ingenierias\Insumos;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InsumoPlantillaExport implements FromArray, WithEvents
{
    private const CATEGORIAS = ['materiales', 'mano_obra', 'maquinaria'];

    public function array(): array
    {
        return [
            ['CODIGO', 'CONCEPTO', 'UNIDAD', 'CATEGORIA', 'CANTIDAD', 'PRECIO (opcional)', 'IMPORTE'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                $sheet->getStyle('A1:G1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');

                foreach (range('A', 'G') as $columna) {
                    $sheet->getColumnDimension($columna)->setWidth(22);
                }

                $lista = implode(',', self::CATEGORIAS);

                for ($fila = 2; $fila <= 200; $fila++) {
                    $validacion = $sheet->getCell("D{$fila}")->getDataValidation();
                    $validacion->setType(DataValidation::TYPE_LIST);
                    $validacion->setAllowBlank(true);
                    $validacion->setShowDropDown(true);
                    $validacion->setShowErrorMessage(true);
                    $validacion->setErrorTitle('Valor inválido');
                    $validacion->setError("Solo se permite: {$lista}");
                    $validacion->setFormula1('"'.$lista.'"');
                }

                $sheet->freezePane('A2');
            },
        ];
    }
}
