<?php

namespace App\Actions\Ingenierias\Cotizaciones;

use App\Actions\Ingenierias\Cotizaciones\Partidas\PartidasAction;
use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

class CotizacionPdfAction
{
    public function __construct(
        private readonly PartidasAction $partidasAction,
    ) {}

    public function generar(Cotizacion $cotizacion): PdfInstance
    {
        $partidas = $this->partidasAction->arbol($cotizacion);

        $logoIzquierdo = $this->logoComoBase64(public_path('apple-touch-icon.png'));
        $logoDerecho = $this->logoComoBase64(public_path('iso-certified.png'));

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'partidas' => $partidas,
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
