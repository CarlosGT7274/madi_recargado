<?php

namespace App\Support;

class NumeroALetras
{
    /** @var array<int, string> */
    private const UNIDADES = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
        'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS',
        'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE',
    ];

    /** @var array<int, string> */
    private const DECENAS = [
        3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA',
        6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA',
    ];

    /** @var array<int, string> */
    private const CENTENAS = [
        1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS',
        5 => 'QUINIENTOS', 6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS',
        9 => 'NOVECIENTOS',
    ];

    /**
     * Convierte un importe a su representación en letras para documentos
     * comerciales: "OCHO MIL DOSCIENTOS CINCUENTA PESOS 00/100 M.N."
     *
     * Se calcula siempre a partir del monto numérico — nunca se captura
     * a mano, ni en el formulario ni en la plantilla de Excel.
     */
    public static function convertir(float $monto, string $moneda = 'PESOS'): string
    {
        $montoRedondeado = round(abs($monto), 2);
        $entero = (int) floor($montoRedondeado);
        $centavos = (int) round(($montoRedondeado - $entero) * 100);

        $letras = $entero === 0 ? 'CERO' : trim(self::grupoMillones($entero));
        $unidadMoneda = $entero === 1 ? self::singularMoneda($moneda) : mb_strtoupper($moneda);

        return sprintf('%s %s %02d/100 M.N.', $letras, $unidadMoneda, $centavos);
    }

    private static function singularMoneda(string $moneda): string
    {
        return match (mb_strtoupper(trim($moneda))) {
            'PESOS', 'PESOS MXN', 'MXN' => 'PESO',
            'DOLARES', 'DÓLARES', 'USD' => 'DÓLAR',
            default => mb_strtoupper($moneda),
        };
    }

    private static function grupoMillones(int $numero): string
    {
        if ($numero >= 1_000_000) {
            $millones = intdiv($numero, 1_000_000);
            $resto = $numero % 1_000_000;
            $prefijo = $millones === 1 ? 'UN MILLÓN' : trim(self::grupoMiles($millones)).' MILLONES';

            return trim($prefijo.' '.($resto > 0 ? self::grupoMiles($resto) : ''));
        }

        return self::grupoMiles($numero);
    }

    private static function grupoMiles(int $numero): string
    {
        if ($numero >= 1000) {
            $miles = intdiv($numero, 1000);
            $resto = $numero % 1000;
            $prefijo = $miles === 1 ? 'MIL' : trim(self::grupoCientos($miles)).' MIL';

            return trim($prefijo.' '.($resto > 0 ? self::grupoCientos($resto) : ''));
        }

        return self::grupoCientos($numero);
    }

    private static function grupoCientos(int $numero): string
    {
        if ($numero === 100) {
            return 'CIEN';
        }

        if ($numero >= 100) {
            $centena = intdiv($numero, 100);
            $resto = $numero % 100;

            return trim(self::CENTENAS[$centena].' '.($resto > 0 ? self::grupoDecenas($resto) : ''));
        }

        return self::grupoDecenas($numero);
    }

    private static function grupoDecenas(int $numero): string
    {
        if ($numero <= 29) {
            return self::UNIDADES[$numero];
        }

        $decena = intdiv($numero, 10);
        $unidad = $numero % 10;

        if ($unidad === 0) {
            return self::DECENAS[$decena];
        }

        return self::DECENAS[$decena].' Y '.self::UNIDADES[$unidad];
    }
}
