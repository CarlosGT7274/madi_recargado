<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Levantamiento {{ $levantamiento->folio }}</title>
    <style>
        @page {
            margin: 130px 40px 70px 40px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        /* ===== Header fijo ===== */
        header {
            position: fixed;
            top: -110px;
            left: 0;
            right: 0;
            height: 100px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .header-logo-left,
        .header-logo-right {
            width: 90px;
        }

        .header-logo-left img {
            height: 55px;
        }

        .header-logo-right img {
            height: 60px;
            float: right;
        }

        .header-center {
            text-align: center;
        }

        .header-center .titulo-empresa {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #a40c32;
            margin: 0 0 2px 0;
        }

        .header-center .titulo-doc {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
            margin: 0 0 2px 0;
        }

        .header-center .folio-doc {
            font-size: 11px;
            color: #4b5563;
            margin: 0;
        }

        .header-rule {
            border-bottom: 2px solid #a40c32;
            margin-top: 6px;
        }

        /* ===== Footer fijo ===== */
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            font-size: 9px;
            color: #6b7280;
        }

        .footer-rule {
            border-top: 1px solid #d1d5db;
            margin-bottom: 6px;
        }

        .footer-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .footer-table td {
            width: 33.33%;
            vertical-align: middle;
            padding: 0;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        /* ===== Contenido ===== */
        h2.seccion {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #ffffff;
            background: #a40c32;
            padding: 5px 8px;
            margin: 16px 0 8px 0;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.datos td {
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            vertical-align: top;
        }

        table.datos td.label {
            width: 32%;
            background: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }

        table.riesgos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.riesgos th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            text-align: left;
            font-size: 10px;
        }

        table.riesgos td {
            border: 1px solid #d1d5db;
            padding: 5px 8px;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-si {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-no {
            background: #f3f4f6;
            color: #6b7280;
        }

        .notas-box {
            border: 1px solid #d1d5db;
            padding: 8px;
            min-height: 24px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>

<header>
    <table class="header-table">
        <tr>
            <td class="header-logo-left">
                @if($logoIzquierdo)
                    <img src="{{ $logoIzquierdo }}" alt="Logo">
                @endif
            </td>
            <td class="header-center">
                <p class="titulo-empresa">MADI</p>
                <p class="titulo-doc">Levantamiento de Requerimientos</p>
                <p class="folio-doc">Folio: {{ $levantamiento->folio }}</p>
            </td>
            <td class="header-logo-right">
                @if($logoDerecho)
                    <img src="{{ $logoDerecho }}" alt="ISO">
                @endif
            </td>
        </tr>
    </table>
    <div class="header-rule"></div>
</header>

<footer>
    <div class="footer-rule"></div>
    <table class="footer-table">
        <tr>
            <td class="footer-left">Folio: {{ $levantamiento->folio }}</td>
            <td class="footer-center">MADI</td>
            <td class="footer-right"></td>
        </tr>
    </table>
</footer>

<main>
    <h2 class="seccion">Identificación</h2>
    <table class="datos">
        <tr>
            <td class="label">Folio</td>
            <td>{{ $levantamiento->folio }}</td>
            <td class="label">Prioridad</td>
            <td>{{ $prioridadLabel[$levantamiento->prioridad] ?? $levantamiento->prioridad }}</td>
        </tr>
        <tr>
            <td class="label">Obra</td>
            <td colspan="3">{{ $levantamiento->proyecto->nombre ?? '—' }}</td>
        </tr>
    </table>

    <h2 class="seccion">Datos Generales</h2>
    <table class="datos">
        <tr>
            <td class="label">Solicitante</td>
            <td>{{ $levantamiento->solicitante ?? '—' }}</td>
            <td class="label">Fecha Solicitud</td>
            <td>{{ optional($levantamiento->fecha_solicitud)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Nombre del Usuario Requiriente</td>
            <td>{{ $levantamiento->usuario_requiriente ?? '—' }}</td>
            <td class="label">Fecha Compromiso</td>
            <td>{{ optional($levantamiento->fecha_levantamiento_programada)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Correo del Usuario Requiriente</td>
            <td>{{ $levantamiento->correo_usuario ?? '—' }}</td>
            <td class="label">Fecha Envío Cotización</td>
            <td>{{ optional($levantamiento->fecha_envio_cotizacion_programada)->format('d/m/Y') ?? '—' }}</td>
        </tr>

        <tr>
            <td class="label">Título Cotización</td>
            <td colspan="3">{{ $levantamiento->titulo_cotizacion ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Medio de Solicitud</td>
            <td colspan="3">{{ $medioSolicitudLabel[$levantamiento->medio_solicitud] ?? ($levantamiento->medio_solicitud ?? '—') }}</td>
        </tr>
    </table>

    <h2 class="seccion">Trabajos Especiales</h2>
    <table class="riesgos">
        <tr>
            <th>Actividad</th>
            <th>¿Aplica?</th>
            <th>¿Personal Certificado?</th>
            <th>Notas</th>
        </tr>
        @foreach ($riesgos as $riesgo)
            <tr>
                <td>{{ $riesgo['titulo'] }}</td>
                <td>
                    <span class="badge {{ $riesgo['aplica'] ? 'badge-si' : 'badge-no' }}">
                        {{ $riesgo['aplica'] ? 'SÍ' : 'NO' }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $riesgo['certificado'] ? 'badge-si' : 'badge-no' }}">
                        {{ $riesgo['certificado'] ? 'SÍ' : 'NO' }}
                    </span>
                </td>
                <td>{{ $riesgo['notas'] ?: '—' }}</td>
            </tr>
        @endforeach
    </table>

    <h2 class="seccion">Maquinaria y Notas</h2>
    <p style="margin: 0 0 3px 0; font-weight: bold; color: #374151;">Notas de Maquinaria</p>
    <div class="notas-box">{{ $levantamiento->notas_maquinaria ?: '—' }}</div>

    <p style="margin: 10px 0 3px 0; font-weight: bold; color: #374151;">Notas Admin</p>
    <div class="notas-box">{{ $levantamiento->notas_admin ?: '—' }}</div>
</main>

<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->getFont("Helvetica", "normal");
        $size = 9;

        $pageText = "Página {PAGE_NUM} de {PAGE_COUNT}";
        $width = $fontMetrics->getTextWidth($pageText, $font, $size);
        $x = $pdf->get_width() - 40 - $width;
        $y = $pdf->get_height() - 45;
        $pdf->page_text($x, $y, $pageText, $font, $size, array(0.42, 0.45, 0.5));
    }
</script>

</body>
</html>
