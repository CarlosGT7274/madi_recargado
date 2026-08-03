<?php

namespace App\Support\Ingenierias;

class PartidaCampos
{
    /**
     * @return array<string, string>
     */
    public static function mapa(): array
    {
        return [
            'descripcion' => 'Descripción',
            'cantidad' => 'Cantidad',
            'unidad' => 'Unidad',
            'precio_unitario' => 'Precio Unitario',
            'costo_hora' => 'Costo por Hora (estimado)',
        ];
    }
}
