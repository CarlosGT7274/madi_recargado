<?php

namespace App\Enums;

enum ConceptoNomina: string
{
    case Isr = 'isr';
    case Imss = 'imss';
    case Infonavit = 'infonavit';
    case Prestamo = 'prestamo';
    case Faltas = 'faltas';
    case BonoPuntualidad = 'bono_puntualidad';
    case PrimaVacacional = 'prima_vacacional';
    case HorasExtra = 'horas_extra';
    case Otro = 'otro';

    public function tipo(): string
    {
        return match ($this) {
            self::Isr, self::Imss, self::Infonavit, self::Prestamo, self::Faltas => 'deduccion',
            self::BonoPuntualidad, self::PrimaVacacional, self::HorasExtra, self::Otro => 'percepcion',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Isr => 'ISR',
            self::Imss => 'IMSS',
            self::Infonavit => 'Infonavit',
            self::Prestamo => 'Préstamo',
            self::Faltas => 'Faltas',
            self::BonoPuntualidad => 'Bono de puntualidad',
            self::PrimaVacacional => 'Prima vacacional',
            self::HorasExtra => 'Horas extra',
            self::Otro => 'Otro',
        };
    }
}
