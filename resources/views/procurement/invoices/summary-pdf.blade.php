<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Invoice Vendor</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 22px 24px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .subtitle {
            margin: 4px 0 2px;
            font-size: 11px;
        }

        .meta {
            margin: 0 0 10px;
            font-size: 9px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #222;
            padding: 4px 6px;
            font-size: 9px;
        }

        th {
            background: #f1f5f9;
            text-align: left;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            margin-top: 10px;
            font-size: 10px;
            font-weight: 700;
            text-align: right;
        }
    </style>
</head>
<body>
    <p class="title">Rekap Invoice Vendor (Mingguan)</p>
    <p class="subtitle">Vendor: {{ $vendor->name }}</p>
    <p class="meta">
        Periode: {{ \Illuminate\Support\Carbon::parse($weekStartDate)->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEndDate)->format('d M Y') }}
        | Digenerate: {{ $generatedAt->format('d M Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 22%;">Nomor Invoice</th>
                <th style="width: 18%;">Tanggal</th>
                <th style="width: 22%;">SPPG</th>
                <th style="width: 13%;">Status</th>
                <th style="width: 20%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaryInvoices as $index => $invoice)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                    <td>{{ $invoice->sppg?->name ?? '-' }}</td>
                    <td>{{ $invoice->status?->value ?? '-' }}</td>
                    <td class="text-right">@rupiah($invoice->total_amount)</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada invoice pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="summary">Total Rekap: @rupiah($summaryTotal)</p>
</body>
</html>
