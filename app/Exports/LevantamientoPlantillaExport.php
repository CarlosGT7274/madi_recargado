<?php

namespace App\Exports;

use App\Support\Ingenierias\LevantamientoCampos;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LevantamientoPlantillaExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return []; // plantilla vacía, el usuario llena filas
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_values(LevantamientoCampos::mapa());
    }
}
