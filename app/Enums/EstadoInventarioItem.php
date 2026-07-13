<?php

namespace App\Enums;

enum EstadoInventarioItem: string
{
    case Disponible = 'disponible';
    case Prestado = 'prestado';
    case Danado = 'danado';
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Prestado => 'Prestado',
            self::Danado => 'Dañado',
            self::Baja => 'Dado de baja',
        };
    }
}
