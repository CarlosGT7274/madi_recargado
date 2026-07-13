<?php

namespace App\Enums;

enum EstadoRequisicion: string
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
            self::Cancelada => 'Cancelada',
        };
    }
}
