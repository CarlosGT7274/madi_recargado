<?php

namespace App\Actions\Ingenierias\Insumos;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

class InsumoPdfAction
{
    public function __construct(
        private readonly InsumosAction $insumosAction,
    ) {}

    public function generar(Cotizacion $cotizacion): PdfInstance
    {
        $insumos = $this->insumosAction->list($cotizacion);
        $resumen = $this->insumosAction->resumen($cotizacion);

        $logoIzquierdo = $this->logoComoBase64(public_path('apple-touch-icon.png'));
        $logoDerecho = $this->logoComoBase64(public_path('iso-certified.png'));

        $pdf = Pdf::loadView('pdf.insumos', [
            'cotizacion' => $cotizacion,
            'resumen' => $resumen,
            'porCategoria' => [
                'materiales' => ['titulo' => 'Materiales', 'items' => $insumos->where('categoria', 'materiales')->values()],
                'mano_obra' => ['titulo' => 'Mano de Obra', 'items' => $insumos->where('categoria', 'mano_obra')->values()],
                'maquinaria' => ['titulo' => 'Maquinaria', 'items' => $insumos->where('categoria', 'maquinaria')->values()],
            ],
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
