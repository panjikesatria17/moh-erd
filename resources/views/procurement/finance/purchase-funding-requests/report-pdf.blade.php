<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengajuan Dana Pembelian</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 18px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 10px;
            font-size: 10px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th,
        td {
            border: 1px solid #222;
            padding: 4px 5px;
            font-size: 9px;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            margin-top: 10px;
        }

        .summary td {
            font-size: 10px;
            padding: 4px 6px;
        }

        .summary .label {
            width: 170px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <h1>Laporan Pengajuan Dana Pembelian</h1>

    <p class="meta">
        Status: <strong>{{ $selectedStatus ? str($selectedStatus)->replace('_', ' ')->title() : 'Semua' }}</strong>
        | Sumber Dana: <strong>{{ $selectedFundSource ? str($selectedFundSource)->replace('_', ' ')->title() : 'Semua' }}</strong>
        | Generated: <strong>{{ $generatedAt->format('d M Y H:i') }}</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Nomor</th>
                <th style="width: 11%;">PO</th>
                <th style="width: 14%;">Vendor</th>
                <th style="width: 12%;">SPPG</th>
                <th style="width: 9%;">Sumber</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 11%;" class="text-right">Diajukan</th>
                <th style="width: 11%;" class="text-right">Approved</th>
                <th style="width: 11%;" class="text-right">Cair</th>
                <th style="width: 10%;" class="text-right">Sisa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fundingRequests as $fundingRequest)
                @php
                    $remainingAmount = max((float) ($fundingRequest->disbursed_amount ?? 0) - (float) ($fundingRequest->spent_amount ?? 0), 0);
                @endphp
                <tr>
                    <td>{{ $fundingRequest->number }}</td>
                    <td>{{ $fundingRequest->purchaseOrder?->number ?? '-' }}</td>
                    <td>{{ $fundingRequest->vendor?->name ?? '-' }}</td>
                    <td>{{ $fundingRequest->sppg?->name ?? '-' }}</td>
                    <td>{{ str($fundingRequest->fund_source)->replace('_', ' ')->title() }}</td>
                    <td>{{ str($fundingRequest->status?->value ?? '-')->replace('_', ' ')->title() }}</td>
                    <td class="text-right">@rupiah($fundingRequest->requested_amount)</td>
                    <td class="text-right">@rupiah($fundingRequest->approved_amount ?? 0)</td>
                    <td class="text-right">@rupiah($fundingRequest->disbursed_amount ?? 0)</td>
                    <td class="text-right">@rupiah($remainingAmount)</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center;">Tidak ada data sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Total Diajukan</td>
            <td class="text-right">@rupiah($totals['requested'] ?? 0)</td>
            <td class="label">Total Approved</td>
            <td class="text-right">@rupiah($totals['approved'] ?? 0)</td>
        </tr>
        <tr>
            <td class="label">Total Cair</td>
            <td class="text-right">@rupiah($totals['disbursed'] ?? 0)</td>
            <td class="label">Total Terpakai</td>
            <td class="text-right">@rupiah($totals['spent'] ?? 0)</td>
        </tr>
        <tr>
            <td class="label">Sisa Dana Berjalan</td>
            <td class="text-right">@rupiah($totals['remaining'] ?? 0)</td>
            <td></td>
            <td></td>
        </tr>
    </table>
</body>
</html>
