<?php

namespace App\Actions\Ingenierias\Levantamientos;

use App\Models\Levantamiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

class LevantamientoPdfAction
{
    private const PRIORIDAD_LABEL = [
        'normal' => 'Normal',
        'urgente' => 'Urgente',
        'grande_compleja' => 'Grande / Compleja',
    ];

    private const MEDIO_SOLICITUD_LABEL = [
        'portal' => 'Portal',
        'correo' => 'Correo',
        'whatsapp' => 'WhatsApp',
        'telefono' => 'Teléfono',
    ];

    public function generar(Levantamiento $levantamiento): PdfInstance
    {
        $levantamiento->loadMissing('proyecto');

        $riesgos = [
            $this->filaRiesgo($levantamiento, 'Trabajos en Alturas', 'trabajos_alturas_certificado', null, 'trabajos_alturas_notas', siempreAplica: true),
            $this->filaRiesgo($levantamiento, 'Espacios Confinados', 'espacios_confinados_certificado', 'espacios_confinados_aplica', 'espacios_confinados_notas'),
            $this->filaRiesgo($levantamiento, 'Corte y Soldadura', 'corte_soldadura_certificado', 'corte_soldadura_aplica', 'corte_soldadura_notas'),
            $this->filaRiesgo($levantamiento, 'Izaje', 'izaje_certificado', 'izaje_aplica', 'izaje_notas'),
            $this->filaRiesgo($levantamiento, 'Apertura de Líneas', 'apertura_lineas_certificado', 'apertura_lineas_aplica', 'apertura_lineas_notas'),
            $this->filaRiesgo($levantamiento, 'Excavación', 'excavacion_certificado', 'excavacion_aplica', 'excavacion_notas'),
        ];

        $logoIzquierdo = $this->logoComoBase64(public_path('apple-touch-icon.png'));
        $logoDerecho = $this->logoComoBase64(public_path('iso-certified.png'));

        $pdf = Pdf::loadView('pdf.levantamiento', [
            'levantamiento' => $levantamiento,
            'riesgos' => $riesgos,
            'prioridadLabel' => self::PRIORIDAD_LABEL,
            'medioSolicitudLabel' => self::MEDIO_SOLICITUD_LABEL,
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

    /**
     * @return array{titulo: string, aplica: bool, certificado: bool, notas: ?string}
     */
    private function filaRiesgo(
        Levantamiento $levantamiento,
        string $titulo,
        string $campoCertificado,
        ?string $campoAplica,
        string $campoNotas,
        bool $siempreAplica = false,
    ): array {
        return [
            'titulo' => $titulo,
            'aplica' => $siempreAplica || (bool) $levantamiento->{$campoAplica},
            'certificado' => (bool) $levantamiento->{$campoCertificado},
            'notas' => $levantamiento->{$campoNotas},
        ];
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
