<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero - {{ $title ?? 'Rentame' }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9.5pt;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .brand-title {
            font-size: 16pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }
        .kpi-cards {
            width: 100%;
            margin-bottom: 15px;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .kpi-val {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 4px;
        }
        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 4px 8px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-left: 3px solid #2563eb;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            font-size: 8.5pt;
            text-transform: uppercase;
            color: #64748b;
        }
        table.data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9pt;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-success {
            color: #16a34a;
        }
        .text-danger {
            color: #dc2626;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
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
                    <div style="font-size: 8.5pt; color: #64748b;">Sistema de Gestión de Rentas y Control Financiero</div>
                </td>
                <td class="text-right">
                    <div class="report-title">{{ $title ?? 'Reporte Financiero' }}</div>
                    <div style="font-size: 8.5pt; color: #64748b;">Periodo: {{ $period ?? 'Histórico Completo' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KPIs -->
    @if (isset($kpis))
    <table class="kpi-cards">
        <tr>
            @foreach ($kpis as $kpi)
            <td style="width: {{ 100 / count($kpis) }}%; padding: 0 4px;">
                <div class="kpi-card">
                    <div style="font-size: 8pt; color: #64748b; text-transform: uppercase;">{{ $kpi['label'] }}</div>
                    <div class="kpi-val" style="color: {{ $kpi['color'] ?? '#0f172a' }};">{{ $kpi['value'] }}</div>
                </div>
            </td>
            @endforeach
        </tr>
    </table>
    @endif

    <!-- Data Table -->
    @if (isset($items) && count($items) > 0)
    <div class="section-title">Detalle de Registros</div>
    <table class="data-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                <th class="{{ $col['align'] ?? '' }}">{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $row)
            <tr>
                @foreach ($columns as $col)
                <td class="{{ $col['align'] ?? '' }}">
                    @php $key = $col['key']; @endphp
                    @if (isset($col['format']) && $col['format'] === 'currency')
                        ${{ number_format(($row[$key] ?? 0) / 100, 2) }} MXN
                    @else
                        {{ $row[$key] ?? '—' }}
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} | Rentame Pro - Software de Arrendamiento
    </div>

</body>
</html>
