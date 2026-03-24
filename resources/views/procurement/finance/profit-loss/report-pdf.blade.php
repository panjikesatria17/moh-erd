<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 20px;
        }
        .header {
            margin-bottom: 14px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }
        .subtitle {
            margin-top: 4px;
            color: #4b5563;
            font-size: 10px;
        }
        .summary {
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
        }
        .summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary td {
            padding: 7px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .summary tr:last-child td {
            border-bottom: none;
        }
        .label {
            width: 35%;
            color: #374151;
            font-weight: 600;
        }
        .value {
            text-align: right;
            font-weight: 700;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d1d5db;
        }
        .table th,
        .table td {
            border: 1px solid #e5e7eb;
            padding: 7px;
        }
        .table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }
        .table td.numeric,
        .table th.numeric {
            text-align: right;
        }
        .footer {
            margin-top: 10px;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Laporan Laba Rugi</h1>
        <div class="subtitle">Periode {{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td class="label">Pendapatan Realisasi</td>
                <td class="value">Rp {{ number_format((float) $realizedRevenue, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">HPP (COGS)</td>
                <td class="value">Rp {{ number_format((float) $cogs, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Laba Kotor</td>
                <td class="value">Rp {{ number_format((float) $grossProfit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Realisasi Pembayaran</td>
                <td class="value">Rp {{ number_format((float) $paidExpense, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Laba Bersih (Operasional)</td>
                <td class="value">Rp {{ number_format((float) $netProfit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Invoice</td>
                <td class="value">{{ number_format((int) $totalInvoicedDocuments, 0, ',', '.') }} dokumen</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>SPPG</th>
                <th class="numeric">Pendapatan Realisasi</th>
                <th class="numeric">HPP (COGS)</th>
                <th class="numeric">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sppgRows as $row)
                <tr>
                    <td>{{ $row['sppg_name'] ?? '-' }}</td>
                    <td class="numeric">Rp {{ number_format((float) ($row['realized_revenue'] ?? 0), 0, ',', '.') }}</td>
                    <td class="numeric">Rp {{ number_format((float) ($row['cogs'] ?? 0), 0, ',', '.') }}</td>
                    <td class="numeric">Rp {{ number_format((float) ($row['gross_profit'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#6b7280;">Belum ada data invoice pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ optional($generatedAt)->format('d M Y H:i') }}.
    </div>
</body>
</html>
