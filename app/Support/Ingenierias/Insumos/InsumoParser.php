<?php

namespace App\Support\Ingenierias\Insumos;

use Illuminate\Support\Collection;

interface InsumoParser
{
    public function parsear(Collection $filas): InsumoParseResultado;
}
