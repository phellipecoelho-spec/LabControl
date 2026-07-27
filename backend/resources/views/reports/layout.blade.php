<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Relatório LabControl' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }
        .header img {
            max-height: 60px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 5px 0;
            color: #1e3a5f;
        }
        .header p {
            font-size: 8pt;
            color: #666;
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        thead th {
            background: #2563eb;
            color: white;
            padding: 8px 6px;
            font-size: 9pt;
            text-align: left;
            font-weight: bold;
        }
        tbody td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 8px;
            background: #eef2ff;
        }
        .status-active { color: #16a34a; }
        .status-inactive { color: #6b7280; }
        .status-maintenance { color: #f59e0b; }
        .status-retired { color: #ef4444; }
        .status-scheduled { color: #3b82f6; }
        .status-completed { color: #16a34a; }
        .status-cancelled { color: #ef4444; }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1e3a5f;
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ddd;
        }
        .kpi-grid {
            width: 100%;
            margin-bottom: 10px;
        }
        .kpi-grid td {
            width: 20%;
            padding: 8px;
            text-align: center;
            font-size: 9pt;
            border: 1px solid #ddd;
        }
        .kpi-grid .kpi-value {
            font-size: 14pt;
            font-weight: bold;
            color: #2563eb;
        }
        .kpi-grid .kpi-label {
            font-size: 8pt;
            color: #666;
        }
        .legend {
            font-size: 8pt;
            color: #666;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LabControl</h1>
        <h1 style="font-size: 13pt;">{{ $title }}</h1>
        <p>Gerado em: {{ $generatedAt }}</p>
    </div>

    @yield('content')

    <div class="footer">
        LabControl — Plataforma de Gestão Laboratorial<br>
        Documento gerado automaticamente em {{ $generatedAt }}
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(72, $pdf->get_height() - 30, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 8, array(0.6, 0.6, 0.6));
        }
    </script>
</body>
</html>
