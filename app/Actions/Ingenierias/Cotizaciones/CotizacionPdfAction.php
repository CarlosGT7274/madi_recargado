<?php

namespace App\Actions\Ingenierias\Cotizaciones;

use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
use App\Support\NumeroALetras;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

class CotizacionPdfAction
{
    /**
     * Fija en código a propósito: la dirección de MADI en el membrete del
     * PDF no es un dato de negocio capturable, es la dirección física de
     * la empresa — no tiene sentido guardarla en BD ni en config.
     */
    private const DIRECCION_MADI = 'Valle de Zitácuaro 14 B, Cofradía San Miguel II, Cuautitlán Izcalli, Edo de Méx C.P. 54760';

    /**
     * IVA fijo en 16%, calculado aquí en vez de leído de la columna
     * `iva` de la cotización — esa columna se quedaba en 0 en el flujo
     * de importación por Excel y nunca se llenaba correctamente.
     */
    private const IVA_PORCENTAJE = 0.16;

    public function __construct(
        private readonly PartidasAction $partidasAction,
    ) {}

    public function generar(Cotizacion $cotizacion): PdfInstance
    {
        $partidas = $this->partidasAction->arbol($cotizacion);

        $subtotal = (float) $cotizacion->subtotal;
        $iva = round($subtotal * self::IVA_PORCENTAJE, 2);
        $total = $subtotal + $iva;

        // Derivado del total, nunca capturado a mano (ni en Excel ni en
        // el formulario manual).
        $importeLetra = NumeroALetras::convertir($total, $cotizacion->moneda ?? 'PESOS');

        $logoIzquierdo = $this->logoComoBase64(public_path('apple-touch-icon.png'));
        $logoDerecho = $this->logoComoBase64(public_path('iso-certified.png'));

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'partidas' => $partidas,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'ivaPorcentaje' => self::IVA_PORCENTAJE * 100,
            'total' => $total,
            'importeLetra' => $importeLetra,
            'direccionMadi' => self::DIRECCION_MADI,
            'logoIzquierdo' => $logoIzquierdo,
            'logoDerecho' => $logoDerecho,
        ]);

        $pdf->setPaper('letter', 'portrait');
        $pdf->setOptions([
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf;
    }

    private function logoComoBase64(string $ruta): ?string
    {
        if (! file_exists($ruta)) {
            return null;
        }

        $tipo = pathinfo($ruta, PATHINFO_EXTENSION) === 'svg' ? 'svg+xml' : 'png';
        $contenido = base64_encode(file_get_contents($ruta));

        return "data:image/{$tipo};base64,{$contenido}";
    }
}
