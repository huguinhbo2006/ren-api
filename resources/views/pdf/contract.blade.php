<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Arrendamiento - {{ $rental->folio }}</title>
    <style>
        @page {
            margin: 25mm 20mm 20mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11pt;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .brand-title {
            font-size: 20pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        .folio-box {
            text-align: right;
        }
        .folio-number {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 6px 10px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #2563eb;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 30%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 10pt;
            text-align: left;
            color: #475569;
        }
        .data-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 10pt;
        }
        .text-right {
            text-align: right;
        }
        .total-box {
            width: 100%;
            margin-top: 10px;
        }
        .total-table {
            width: 50%;
            float: right;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 4px 8px;
            font-size: 10pt;
        }
        .total-table .grand-total {
            font-size: 12pt;
            font-weight: bold;
            border-top: 2px solid #2563eb;
            color: #2563eb;
        }
        .clauses {
            font-size: 8.5pt;
            color: #64748b;
            line-height: 1.4;
            margin-top: 30px;
            text-align: justify;
        }
        .signatures {
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-size: 10pt;
        }
    </style>
</head>
<body>

    <!-- Encabezado -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand-title">{{ $user->name ?? 'Rentame' }}</div>
                    <div style="font-size: 9pt; color: #64748b;">Sistema de Gestión de Rentas</div>
                </td>
                <td class="folio-box">
                    <div style="font-size: 9pt; color: #64748b; text-transform: uppercase;">Contrato de Arrendamiento</div>
                    <div class="folio-number">{{ $rental->folio }}</div>
                    <div style="font-size: 9pt; color: #64748b;">Fecha: {{ $rental->created_at->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Partes Involucradas -->
    <div class="section-title">1. Partes del Contrato</div>
    <table class="info-table">
        <tr>
            <td class="label">Arrendador (Propietario):</td>
            <td><strong>{{ $user->name }}</strong> ({{ $user->email }})</td>
        </tr>
        <tr>
            <td class="label">Arrendatario (Cliente):</td>
            <td>
                <strong>{{ $rental->customer->name }}</strong><br>
                Tel: {{ $rental->customer->phone }} | RFC: {{ $rental->customer->rfc ?? 'Sin RFC' }}<br>
                Dirección: {{ $rental->customer->address ?? 'No registrada' }}
            </td>
        </tr>
    </table>

    <!-- Objeto y Periodo -->
    <div class="section-title">2. Objeto Arrendado y Periodo</div>
    <table class="info-table">
        <tr>
            <td class="label">Bien / Activo:</td>
            <td><strong>{{ $rental->asset->name }}</strong></td>
        </tr>
        @if ($rental->asset->serial_number)
        <tr>
            <td class="label">Número de Serie:</td>
            <td>{{ $rental->asset->serial_number }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Periodo de Renta:</td>
            <td>
                Del <strong>{{ $rental->start_date->format('d/m/Y') }}</strong> al <strong>{{ $rental->end_date->format('d/m/Y') }}</strong>
                ({{ $rental->rental_days }} {{ $rental->rental_days === 1 ? 'día' : 'días' }})
            </td>
        </tr>
    </table>

    <!-- Desglose Financiero -->
    <div class="section-title">3. Condiciones Financieras</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio Unitario</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Renta de {{ $rental->asset->name }} ({{ $rental->rental_days }} días)</td>
                <td class="text-right">1</td>
                <td class="text-right">${{ number_format($rental->base_amount_cents / 100, 2) }}</td>
                <td class="text-right">${{ number_format($rental->base_amount_cents / 100, 2) }}</td>
            </tr>
            @foreach ($rental->extras as $extra)
            <tr>
                <td>Servicio Extra: {{ $extra->name }}</td>
                <td class="text-right">{{ $extra->quantity }}</td>
                <td class="text-right">${{ number_format($extra->unit_price_cents / 100, 2) }}</td>
                <td class="text-right">${{ number_format($extra->total_price_cents / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="total-box">
        <table class="total-table">
            @if ($rental->deposit_cents > 0)
            <tr>
                <td>Depósito en Garantía:</td>
                <td class="text-right">${{ number_format($rental->deposit_cents / 100, 2) }}</td>
            </tr>
            @endif
            @if ($rental->discount_cents > 0)
            <tr>
                <td>Descuento:</td>
                <td class="text-right">-${{ number_format($rental->discount_cents / 100, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Total a Pagar:</td>
                <td class="text-right">${{ number_format($rental->total_amount_cents / 100, 2) }} MXN</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
    </div>

    <!-- Cláusulas Generales -->
    <div class="clauses">
        <strong>CLÁUSULAS:</strong><br>
        1. <strong>RECEPCIÓN Y ESTADO:</strong> El Arrendatario reconoce recibir el bien mueble en óptimas condiciones físicas y operativas.<br>
        2. <strong>USO CONVENIDO:</strong> El bien será destinado exclusivamente para los fines propios de su naturaleza, asumiendo el Arrendatario total responsabilidad por su cuidado y custodia.<br>
        3. <strong>DEVOLUCIÓN Y MORA:</strong> La entrega deberá realizarse en la fecha pactada. Cualquier prórroga generará cargos adicionales calculados con base en la tarifa diaria pactada.<br>
        4. <strong>DEPÓSITO EN GARANTÍA:</strong> El depósito otorgado responderá por pérdidas, faltantes o daños causados al bien durante el periodo de arrendamiento.
    </div>

    <!-- Firmas -->
    <table class="signatures">
        <tr>
            <td class="signature-box">
                <strong>EL ARRENDADOR</strong><br>
                {{ $user->name }}
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-box">
                <strong>EL ARRENDATARIO</strong><br>
                {{ $rental->customer->name }}
            </td>
        </tr>
    </table>

</body>
</html>
