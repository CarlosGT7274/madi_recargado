<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Illuminate\Support\Collection;

class TestImport extends DefaultValueBinder implements ToCollection, WithColumnLimit, WithCustomValueBinder {
    public function endColumn(): string { return 'F'; }
    public function bindValue(Cell $cell, mixed $value): bool {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
        return true;
    }
    public function collection(Collection $filas): void {
        foreach ($filas->take(20) as $i => $fila) {
            echo "Row $i: " . json_encode($fila) . "\n";
        }
    }
}

class TestImportNormal implements ToCollection {
    public function collection(Collection $filas): void {
        foreach ($filas->take(20) as $i => $fila) {
            echo "Row $i: " . json_encode($fila) . "\n";
        }
    }
}

// Create a dummy excel file using PhpSpreadsheet
$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Fecha:');
$sheet->setCellValue('B1', '19/08/2026');
$sheet->setCellValue('A2', 'Cliente:');
$sheet->setCellValue('B2', 'BASF');
$sheet->setCellValue('A3', 'No.');
$sheet->setCellValue('B3', 'Concepto');
$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('test.xlsx');

echo "--- With Custom Binder & Limit ---\n";
Excel::import(new TestImport, 'test.xlsx');

echo "\n--- Normal ---\n";
Excel::import(new TestImportNormal, 'test.xlsx');
