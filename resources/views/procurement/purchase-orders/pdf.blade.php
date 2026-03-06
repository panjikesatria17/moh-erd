<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentNumber }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 18px 24px;
        }

        .sheet {
            width: 100%;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-wrap {
            width: 14%;
            text-align: center;
        }

        .logo {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .header-center {
            width: 72%;
            text-align: center;
            line-height: 1.25;
        }

        .header-title {
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            margin: 0;
        }

        .header-sppg {
            font-weight: 700;
            font-size: 28px;
            margin: 1px 0;
            text-transform: uppercase;
        }

        .header-org {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            margin: 0;
        }

        .header-address {
            font-size: 10px;
            margin: 0;
        }

        .city-date {
            text-align: right;
            margin: 2px 0 12px;
        }

        .meta {
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .meta p {
            margin: 0 0 6px;
        }

        .need-date {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 0;
        }

        .need-date td {
            padding: 0;
            vertical-align: top;
        }

        .need-date .label {
            width: 255px;
        }

        .need-date .sep {
            width: 10px;
            text-align: center;
        }

        table.grid {
            width: 76%;
            margin: 0 auto;
            border-collapse: collapse;
        }

        .grid th,
        .grid td {
            border: 1px solid #000;
            padding: 1px 3px;
            font-size: 10px;
            line-height: 1.2;
        }

        .grid th {
            text-align: center;
            font-weight: 700;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .rp-cell {
            white-space: nowrap;
        }

        .closing {
            margin: 14px 0 0;
            line-height: 1.5;
        }

        .signature {
            margin-top: 10px;
            text-align: center;
            line-height: 1.4;
        }

        .signature-space {
            height: 68px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    @php
        $docDate = $documentDate ? \Illuminate\Support\Carbon::parse($documentDate) : $generatedAt;
        $needDate = $neededDate ? \Illuminate\Support\Carbon::parse($neededDate) : $docDate;

        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        $monthMap = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $needDay = $dayMap[$needDate->format('l')] ?? $needDate->format('l');
        $needDateLabel = $needDate->format('d').' '.($monthMap[(int) $needDate->format('n')] ?? $needDate->format('F')).' '.$needDate->format('Y');
        $cityDate = 'Bogor, '.$generatedAt->format('d').' '.($monthMap[(int) $generatedAt->format('n')] ?? $generatedAt->format('F')).' '.$generatedAt->format('Y');

        $rows = collect($itemsRows ?? []);
        $minimumRows = 16;
        $blankRows = max(0, $minimumRows - $rows->count());
    @endphp

    <div class="sheet">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-wrap">
                        @if($logoBgn)
                            <img src="{{ $logoBgn }}" alt="BGN" class="logo">
                        @endif
                    </td>
                    <td class="header-center">
                        <p class="header-title">SATUAN PELAYANAN PEMENUHAN GIZI (SPPG)</p>
                        <p class="header-sppg">{{ strtoupper($senderName ?? '-') }}</p>
                        <p class="header-org">YAYASAN SATRIA MERAH PUTIH</p>
                        <p class="header-address">{{ $senderAddress ?? '-' }}</p>
                    </td>
                    <td class="logo-wrap">
                        @if($logoSmp)
                            <img src="{{ $logoSmp }}" alt="SMP" class="logo">
                        @elseif($logoSppg)
                            <img src="{{ $logoSppg }}" alt="SPPG" class="logo">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <p class="city-date">{{ $cityDate }}</p>

        <div class="meta">
            <p>Kepada Yth, Mitra</p>
            <p>Kami dari {{ $senderName ?? '-' }} ingin mengajukan pembelian bahan baku yang akan kami gunakan:</p>
            <table class="need-date">
                <tr>
                    <td class="label">Hari, Tanggal Kebutuhan</td>
                    <td class="sep">:</td>
                    <td>{{ $needDay }}, {{ $needDateLabel }}</td>
                </tr>
            </table>
        </div>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 37%;">Jenis Bahan</th>
                    <th style="width: 12%;">Q</th>
                    <th style="width: 13%;">Satuan</th>
                    <th style="width: 16%;">Harga Mitra</th>
                    <th style="width: 17%;">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format((float) ($row['qty'] ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-center">{{ $row['unit'] ?? '-' }}</td>
                        <td class="text-right rp-cell">Rp {{ number_format((float) ($row['unit_price'] ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right rp-cell">Rp {{ number_format((float) ($row['total_price'] ?? 0), 2, ',', '.') }}</td>
                    </tr>
                @endforeach

                @for($i = 0; $i < $blankRows; $i++)
                    <tr>
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor

                <tr>
                    <td colspan="5"></td>
                    <td class="text-right rp-cell" style="font-weight:700;">Rp {{ number_format((float) $totalAmount, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <p class="closing">Demikian permohonan kami sampaikan, harap dapat dipenuhi dan dikirim tepat waktu.</p>

        <div class="signature">
            <div>Dibuat</div>
            <div class="signature-space"></div>
            <div class="signature-name">{{ $creatorName ?: 'Operator SPPG' }}</div>
            <div>PLOK</div>
        </div>
    </div>
</body>
</html>
