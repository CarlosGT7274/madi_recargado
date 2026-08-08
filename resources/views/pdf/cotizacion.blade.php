<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $cotizacion->folio }}</title>
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
            width: 28%;
            background: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }

        table.partidas {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.partidas th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            text-align: left;
            font-size: 10px;
        }

        table.partidas td {
            border: 1px solid #d1d5db;
            padding: 5px 8px;
        }

        tr.seccion-partida td {
            background: #f9fafb;
            font-weight: bold;
        }

        td.num {
            text-align: right;
        }

        table.totales {
            width: 60%;
            margin-left: 40%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.totales td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
        }

        table.totales td.label {
            background: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }

        table.totales td.valor {
            text-align: right;
            font-weight: bold;
        }

        table.totales tr.total td {
            background: #a40c32;
            color: #ffffff;
            font-size: 12px;
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
                <p class="titulo-doc">Cotización</p>
                <p class="folio-doc">Folio: {{ $cotizacion->folio }}</p>
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
            <td class="footer-left">Folio: {{ $cotizacion->folio }}</td>
            <td class="footer-center">MADI</td>
            <td class="footer-right"></td>
        </tr>
    </table>
</footer>

<main>
    <h2 class="seccion">Información General</h2>
    <table class="datos">
        <tr>
            <td class="label">Folio</td>
            <td>{{ $cotizacion->folio }}</td>
            <td class="label">Fecha</td>
            <td>{{ optional($cotizacion->fecha)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Cliente</td>
            <td>{{ $cotizacion->cliente ?? '—' }}</td>
            <td class="label">Dirección</td>
            <td>{{ $cotizacion->direccion ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Obra</td>
            <td>{{ $cotizacion->obra ?? '—' }}</td>
            <td class="label">Vendedor</td>
            <td>{{ $cotizacion->vendedor ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Proveedor</td>
            <td colspan="3">{{ $cotizacion->proveedor ?? '—' }}</td>
        </tr>
    </table>

    <h2 class="seccion">Partidas</h2>

    @forelse ($partidas as $raiz)
        <table class="partidas">
            <tr class="seccion-partida">
                <td colspan="5">{{ $raiz['no'] }} · {{ $raiz['descripcion'] }}</td>
            </tr>
            <tr>
                <th style="width:50px;">No.</th>
                <th>Concepto</th>
                <th style="width:60px;">Unidad</th>
                <th style="width:70px;">Cantidad</th>
                <th style="width:90px;">P. Unitario</th>
            </tr>
            @foreach ($raiz['hijas'] as $hija)
                <tr>
                    <td>{{ $hija['no'] }}</td>
                    <td>{{ $hija['descripcion'] }}</td>
                    <td>{{ $hija['unidad'] ?? '—' }}</td>
                    <td class="num">{{ number_format($hija['cantidad'], 2) }}</td>
                    <td class="num">${{ number_format($hija['precioUnitario'], 2) }}</td>
                </tr>
            @endforeach
        </table>
    @empty
        <p>Aún no hay partidas registradas.</p>
    @endforelse

    <h2 class="seccion">Totales</h2>
    <table class="totales">
        <tr>
            <td class="label">Subtotal</td>
            <td class="valor">${{ number_format((float) $cotizacion->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">IVA</td>
            <td class="valor">${{ number_format((float) $cotizacion->iva, 2) }}</td>
        </tr>
        <tr class="total">
            <td class="label" style="color:#ffffff; background:transparent;">Total</td>
            <td class="valor">${{ number_format((float) $cotizacion->total, 2) }}</td>
        </tr>
    </table>
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
