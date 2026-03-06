<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $kwitansi->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 14px 16px;
        }

        .sheet {
            width: 100%;
            border: 1px solid #d4d4d4;
            padding: 8px 10px 14px;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
        }

        .logo-wrap {
            width: 120px;
            text-align: left;
        }

        .logo-frame {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #d6d6d6;
            display: inline-block;
            background: #fff;
        }

        .logo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .vendor-name {
            font-size: 21px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            line-height: 1.05;
            margin: 3px 0 1px;
        }

        .vendor-address {
            font-size: 8.8px;
            text-align: left;
            text-transform: uppercase;
            line-height: 1.35;
            margin: 0;
            padding-right: 10px;
        }

        .kwitansi-head {
            text-align: right;
        }

        .kwitansi-title {
            background: #2f7d1f;
            color: #fff;
            font-weight: bold;
            padding: 3px 10px;
            text-align: right;
            font-size: 13px;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            margin-top: 4px;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .meta td {
            border-bottom: 1px solid #000;
            font-size: 9px;
            padding: 2px 4px;
        }

        .meta tr:last-child td {
            border-bottom: none;
        }

        .meta-label {
            width: 54px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta-sep {
            width: 8px;
            text-align: center;
        }

        .detail {
            margin-top: 10px;
            font-size: 10px;
            line-height: 1.45;
        }

        .detail-label {
            display: inline-block;
            width: 125px;
        }

        .row-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .row-table td,
        .row-table th {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 9px;
        }

        .row-table thead th {
            background: #f0f0f0;
            text-align: center;
            text-transform: uppercase;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .label {
            width: 74px;
            display: inline-block;
        }

        .amount-box {
            display: inline-block;
            min-width: 230px;
            border: 1px solid #000;
            padding: 2px 8px;
            font-size: 16px;
            font-weight: bold;
            background: #f3f3f3;
        }

        .signature {
            margin-top: 58px;
            text-align: right;
            font-size: 10px;
        }

        .materai-box {
            display: inline-block;
            margin-top: 8px;
            min-width: 128px;
            border: 1px dashed #777;
            border-radius: 4px;
            padding: 4px 10px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            color: #666;
            background: transparent;
            opacity: .58;
        }

        .materai-top {
            font-size: 8px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .materai-value {
            font-size: 12px;
            line-height: 1.15;
            margin-top: 1px;
        }

        .signature-name {
            margin-top: 28px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .signature-owner {
            margin-top: 5px;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo-wrap">
                    @if(!empty($logoVendor))
                        <span class="logo-frame">
                            <img src="{{ $logoVendor }}" class="logo" alt="Logo Vendor">
                        </span>
                    @endif
                </td>
                <td style="width: 58%;">
                    <div class="vendor-name">{{ $vendor?->name ?? 'VENDOR' }}</div>
                    <p class="vendor-address">{{ strtoupper((string) ($vendor?->address ?? '-')) }}</p>
                </td>
                <td style="width: 22%;" class="kwitansi-head">
                    <div class="kwitansi-title">KWITANSI</div>
                    <table class="meta">
                        <tr>
                            <td class="meta-label">Nomor</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $kwitansi->number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Tanggal</td>
                            <td class="meta-sep">:</td>
                            <td>{{ optional($kwitansi->receipt_date)->format('d F Y') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="detail">
            <div><span class="detail-label">Telah diterima dari</span>: <strong>{{ strtoupper((string) $receivedFrom) }}</strong></div>
            <div><span class="detail-label">Untuk pembayaran</span>: Tagihan gabungan {{ $rows->count() }} invoice vendor</div>
            @if($firstSppgName)
                <div><span class="detail-label">SPPG</span>: {{ $firstSppgName }}</div>
            @endif
        </div>

        <table class="row-table">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Invoice</th>
                    <th>SPPG</th>
                    <th style="width: 100px;">Tanggal</th>
                    <th style="width: 136px;" class="text-right">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="text-center">{{ $row['no'] }}</td>
                        <td>{{ $row['invoice_number'] }}</td>
                        <td>{{ $row['sppg_name'] }}</td>
                        <td class="text-center">{{ optional($row['invoice_date'])->format('d/m/Y') }}</td>
                        <td class="text-right">@rupiah($row['amount'])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 11px;">
            <div><span class="label">Senilai</span>: <span class="amount-box">@rupiah($kwitansi->total_amount)</span></div>
            <div style="margin-top: 4px;"><span class="label">Terbilang</span>: <strong># {{ $terbilang }} #</strong></div>
        </div>

        <div class="signature">
            <div>Yang Menerima,</div>
            <div class="materai-box">
                <div class="materai-top">Materai</div>
                <div class="materai-value">10.000</div>
            </div>
            <div class="signature-name">{{ $vendor?->name ?? '-' }}</div>
            <div class="signature-owner">{{ strtoupper((string) ($ownerName ?? 'OWNER')) }}</div>
        </div>
    </div>
</body>
</html>
