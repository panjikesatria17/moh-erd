<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan {{ $delivery->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 18px 20px;
        }

        .sheet {
            border: 1px solid #d2d6dc;
            padding: 10px 12px;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #111;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }

        .header td {
            vertical-align: middle;
        }

        .logo-left,
        .logo-right {
            width: 90px;
        }

        .logo-right {
            text-align: right;
        }

        .logo-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            display: inline-block;
        }

        .logo {
            width: 100%;
            height: 100%;
            display: block;
        }

        .title {
            text-align: center;
        }

        .title h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: .5px;
        }

        .title p {
            margin: 3px 0 0;
            font-size: 11px;
            color: #4b5563;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .meta td {
            width: 50%;
            vertical-align: top;
            padding: 4px 0;
            font-size: 10px;
            line-height: 1.5;
        }

        .meta strong {
            font-size: 11px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #111;
            padding: 4px 6px;
            font-size: 9px;
        }

        .table th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-weight: 700;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 14px;
            font-size: 9px;
            color: #374151;
        }

        .sign {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        .sign td {
            width: 50%;
            text-align: center;
            font-size: 9px;
        }

        .space {
            height: 56px;
        }

        .name {
            font-weight: 700;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo-left">
                    @if(!empty($logoBgn))
                        <span class="logo-wrap"><img src="{{ $logoBgn }}" alt="BGN" class="logo"></span>
                    @endif
                </td>
                <td class="title">
                    <h1>SURAT JALAN</h1>
                    <p>{{ $delivery->number }}</p>
                </td>
                <td class="logo-right">
                    @if(!empty($logoVendor))
                        <span class="logo-wrap">
                            <img src="{{ $logoVendor }}" alt="Vendor" class="logo" style="border-radius: {{ !empty($hasCustomVendorLogo) ? '8px' : '50%' }};">
                        </span>
                    @endif
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td>
                    <strong>Dari Vendor</strong><br>
                    {{ strtoupper($vendor?->name ?? '-') }}<br>
                    {{ $vendor?->address ?? '-' }}
                </td>
                <td>
                    <strong>Tujuan SPPG</strong><br>
                    {{ strtoupper($sppg?->name ?? '-') }}<br>
                    {{ $sppg?->address ?? '-' }}
                </td>
            </tr>
            <tr>
                <td>
                    Tanggal Kirim: {{ optional($delivery->delivery_date)->format('d M Y') ?? '-' }}<br>
                    Nomor PO: {{ $purchaseOrder?->number ?? '-' }}
                </td>
                <td>
                    Vendor: {{ $vendor?->name ?? '-' }}<br>
                    Generated: {{ $generatedAt->format('d M Y H:i') }}
                </td>
            </tr>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 6%;" class="text-center">No</th>
                    <th style="width: 50%;">Nama Barang</th>
                    <th style="width: 14%;" class="text-right">Qty</th>
                    <th style="width: 12%;" class="text-center">Unit</th>
                    <th style="width: 18%;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemsRows as $row)
                    <tr>
                        <td class="text-center">{{ $row['no'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-right">{{ number_format((float) $row['qty'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ $row['unit'] }}</td>
                        <td>{{ $row['notes'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada item pada PO ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="footer">Dokumen ini adalah surat jalan pengiriman barang per vendor.</p>

        <table class="sign">
            <tr>
                <td>Pengirim (Vendor)</td>
                <td>Penerima (SPPG)</td>
            </tr>
            <tr>
                <td class="space"></td>
                <td class="space"></td>
            </tr>
            <tr>
                <td><span class="name">{{ $vendor?->name ?? '-' }}</span></td>
                <td><span class="name">{{ $sppg?->name ?? '-' }}</span></td>
            </tr>
        </table>
    </div>
</body>
</html>
