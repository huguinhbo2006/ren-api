<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - {{ $payment->rental?->folio ?? 'RECIBO' }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10.5pt;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #16a34a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .brand-title {
            font-size: 18pt;
            font-weight: bold;
            color: #16a34a;
            margin: 0;
        }
        .receipt-number {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e293b;
            background-color: #f0fdf4;
            padding: 5px 10px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #16a34a;
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
        .payment-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        .payment-amount {
            font-size: 22pt;
            font-weight: bold;
            color: #16a34a;
            margin: 5px 0;
        }
        .balance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .balance-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10pt;
        }
        .text-right {
            text-align: right;
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
            font-size: 9.5pt;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand-title">{{ $user->name ?? 'Rentame' }}</div>
                    <div style="font-size: 9pt; color: #64748b;">Comprobante de Ingreso y Cobranza</div>
                </td>
                <td class="text-right">
                    <div class="receipt-number">RECIBO DE PAGO #{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div style="font-size: 9pt; color: #64748b;">Fecha: {{ $payment->payment_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Datos del Cliente y Renta -->
    <div class="section-title">Información del Cliente y Contrato</div>
    <table class="info-table">
        <tr>
            <td class="label">Cliente:</td>
            <td><strong>{{ $payment->rental?->customer?->name ?? 'Cliente General' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Folio de Renta:</td>
            <td><strong>{{ $payment->rental?->folio }}</strong></td>
        </tr>
        <tr>
            <td class="label">Bien / Activo:</td>
            <td>{{ $payment->rental?->asset?->name }}</td>
        </tr>
    </table>

    <!-- Monto Pagado Destacado -->
    <div class="payment-box">
        <div style="font-size: 10pt; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Monto Recibido</div>
        <div class="payment-amount">${{ number_format($payment->amount_cents / 100, 2) }} MXN</div>
        <div style="font-size: 9.5pt; color: #475569;">
            Método: <strong>{{ strtoupper($payment->method) }}</strong>
            @if ($payment->reference)
                | Ref: <strong>{{ $payment->reference }}</strong>
            @endif
        </div>
    </div>

    <!-- Estado de Cuenta de la Renta -->
    <div class="section-title">Balance del Contrato</div>
    @php
        $totalRental = $payment->rental?->total_amount_cents ?? 0;
        $totalPaid = $payment->rental ? $payment->rental->payments->where('type', 'income')->sum('amount_cents') : $payment->amount_cents;
        $pendingBalance = max(0, $totalRental - $totalPaid);
    @endphp
    <table class="balance-table">
        <tr>
            <td>Total Contrato de Renta:</td>
            <td class="text-right"><strong>${{ number_format($totalRental / 100, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Total Acumulado Pagado:</td>
            <td class="text-right" style="color: #16a34a;"><strong>${{ number_format($totalPaid / 100, 2) }}</strong></td>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td>Saldo Pendiente por Liquidar:</td>
            <td class="text-right" style="color: {{ $pendingBalance > 0 ? '#dc2626' : '#16a34a' }};">
                ${{ number_format($pendingBalance / 100, 2) }} MXN
            </td>
        </tr>
    </table>

    @if ($payment->notes)
    <div style="margin-top: 15px; font-size: 9pt; color: #64748b;">
        <strong>Observaciones:</strong> {{ $payment->notes }}
    </div>
    @endif

    <!-- Firmas -->
    <table class="signatures">
        <tr>
            <td class="signature-box">
                <strong>RECIBIÓ CONFORME</strong><br>
                {{ $user->name }}
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-box">
                <strong>ENTREGÓ / CLIENTE</strong><br>
                {{ $payment->rental?->customer?->name ?? 'Cliente' }}
            </td>
        </tr>
    </table>

</body>
</html>
