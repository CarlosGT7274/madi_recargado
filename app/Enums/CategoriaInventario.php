<?php

namespace App\Enums;

enum CategoriaInventario: string
{
    case Herramienta = 'herramienta';
    case Material = 'material';
    case Epp = 'epp';
    case Equipo = 'equipo';

    public function label(): string
    {
        return match ($this) {
            self::Herramienta => 'Herramienta',
            self::Material => 'Material',
            self::Epp => 'Equipo de protección personal',
            self::Equipo => 'Equipo',
        };
    }
}
