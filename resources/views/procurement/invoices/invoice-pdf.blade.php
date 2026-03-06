<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #000;
            margin: 14px 16px;
        }

        .sheet {
            width: 100%;
            border: 1px solid #c8ccd3;
            padding: 8px 10px 12px;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
        }

        .logo-wrap {
            width: 108px;
            text-align: left;
        }

        .logo {
            width: 100%;
            height: 100%;
            display: block;
        }

        .logo-frame {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #d6d6d6;
            display: inline-block;
        }

        .company {
            text-align: right;
        }

        .company-name {
            font-weight: 700;
            font-size: 20px;
            line-height: 1.1;
            margin: 0;
            letter-spacing: .3px;
        }

        .company-address {
            margin: 4px 0 0;
            font-size: 9px;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .invoice-bar {
            margin-top: 6px;
            background: #b88a00;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            text-align: right;
            letter-spacing: .8px;
            padding: 2px 10px;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .meta td {
            vertical-align: top;
            width: 50%;
        }

        .bill-title {
            font-size: 10px;
            margin: 0 0 2px;
        }

        .bill-name {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .bill-address {
            margin: 3px 0 0;
            font-size: 9px;
            line-height: 1.4;
        }

        .doc-meta {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .doc-meta td {
            padding: 2px 4px;
            font-size: 9px;
            line-height: 1.35;
            border-bottom: 1px solid #000;
        }

        .doc-meta tr:last-child td { border-bottom: none; }

        .doc-meta .label {
            width: 64px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .doc-meta .sep {
            width: 8px;
            text-align: center;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items th,
        .items td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 9px;
        }

        .items th {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            background: #f1f1f1;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .footer {
            margin-top: 14px;
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            width: 50%;
            vertical-align: top;
            font-size: 9px;
        }

        .pay-title {
            font-weight: 700;
            margin: 0 0 5px;
            text-transform: uppercase;
        }

        .pay-table {
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .pay-table td {
            padding: 2px 4px;
            font-size: 9px;
            border-bottom: 1px solid #000;
        }

        .pay-table tr:last-child td { border-bottom: none; }

        .pay-table .label { width: 75px; }
        .pay-table .sep { width: 8px; text-align: center; }

        .sign {
            text-align: right;
        }

        .sign-space {
            height: 58px;
        }

        .sign-name {
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .summary-label {
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        $hari = $dayMap[$invoiceDate->format('l')] ?? $invoiceDate->format('l');
        $vendorNameUpper = strtoupper((string) ($vendor?->name ?? ''));
        $isPdMitra = str_contains($vendorNameUpper, 'MITRA');
        $accentColor = $isPdMitra ? '#2F7D1F' : '#B88A00';
        $invoiceBarAlign = $isPdMitra ? 'center' : 'right';
    @endphp

    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo-wrap">
                    @if(!empty($logoVendor))
                        <span class="logo-frame">
                            <img src="{{ $logoVendor }}" alt="Logo" class="logo">
                        </span>
                    @endif
                </td>
                <td class="company">
                    <p class="company-name">{{ strtoupper($vendor?->name ?? '-') }}</p>
                    <p class="company-address">{{ strtoupper($vendor?->address ?? '-') }}</p>
                </td>
            </tr>
        </table>

        <div class="invoice-bar" style="background: {{ $accentColor }}; text-align: {{ $invoiceBarAlign }};">Invoice</div>

        <table class="meta">
            <tr>
                <td>
                    <p class="bill-title">Kepada Yth:</p>
                    <p class="bill-name">{{ strtoupper($sppg?->name ?? '-') }}</p>
                    <p class="bill-address">{{ $sppg?->address ?? '-' }}</p>
                </td>
                <td>
                    <table class="doc-meta">
                        <tr>
                            <td class="label">Nomor</td>
                            <td class="sep">:</td>
                            <td><strong>{{ $invoice->number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal</td>
                            <td class="sep">:</td>
                            <td>{{ $invoiceDate->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Hari</td>
                            <td class="sep">:</td>
                            <td>{{ $hari }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:6%">No</th>
                    <th>Jenis Bahan</th>
                    <th style="width:12%">Satuan</th>
                    <th style="width:13%">Qty</th>
                    <th style="width:15%">Harga</th>
                    <th style="width:18%">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemsRows as $row)
                    <tr>
                        <td class="text-center">{{ $row['no'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-center">{{ $row['unit'] }}</td>
                        <td class="text-center">{{ number_format((float) $row['qty'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float) $row['unit_price'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float) $row['total_price'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada item</td>
                    </tr>
                @endforelse
                @if((float) $taxAmount > 0)
                    <tr>
                        <td colspan="5" class="text-right summary-label">Subtotal</td>
                        <td class="text-right"><strong>Rp {{ number_format((float) $subtotalAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-right summary-label">PPN / Pajak</td>
                        <td class="text-right"><strong>Rp {{ number_format((float) $taxAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif
                <tr>
                    <td colspan="5" class="text-right summary-label">Total</td>
                    <td class="text-right"><strong>Rp {{ number_format((float) $totalAmount, 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <table class="footer">
            <tr>
                <td>
                    <p class="pay-title">Metode Pembayaran:</p>
                    <table class="pay-table">
                        <tr>
                            <td class="label">Metode</td>
                            <td class="sep">:</td>
                            <td>{{ $paymentMethod ?: 'Transfer' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Bank</td>
                            <td class="sep">:</td>
                            <td>{{ $paymentMethod ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">No. Rekening</td>
                            <td class="sep">:</td>
                            <td>{{ $paymentReference ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Atas Nama</td>
                            <td class="sep">:</td>
                            <td>{{ strtoupper($vendor?->name ?? '-') }}</td>
                        </tr>
                    </table>
                </td>
                <td class="sign">
                    <p>Hormat Kami,</p>
                    <div class="sign-space"></div>
                    <p class="sign-name">{{ $signatureName ?? '-' }}</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
