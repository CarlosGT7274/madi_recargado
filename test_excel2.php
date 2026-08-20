<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Illuminate\Support\Collection;

class TestImportLimit implements ToCollection, WithColumnLimit {
    public function endColumn(): string { return 'F'; }
    public function collection(Collection $filas): void {
        foreach ($filas->take(5) as $i => $fila) {
            echo "Row $i: " . json_encode($fila) . "\n";
        }
    }
}

Excel::import(new TestImportLimit, 'test.xlsx');
