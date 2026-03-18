<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentNumber }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 15px 20px;
        }

        .sheet {
            width: 100%;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-wrap {
            width: 12%;
            text-align: center;
        }

        .logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .header-center {
            width: 76%;
            text-align: center;
            line-height: 1.3;
            padding: 0 15px;
        }

        .header-title {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            margin: 0 0 2px;
            letter-spacing: 0.5px;
        }

        .header-sppg {
            font-weight: 700;
            font-size: 20px;
            margin: 2px 0;
            text-transform: uppercase;
        }

        .header-org {
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            margin: 0 0 2px;
        }

        .header-address {
            font-size: 9px;
            margin: 0;
        }

        .doc-type {
            text-align: center;
            margin: 8px 0;
            font-weight: 700;
            font-size: 12px;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .doc-info {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 9px;
        }

        .doc-info-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
        }

        .info-left,
        .info-right {
            width: 48%;
        }

        .info-label {
            font-weight: 700;
            display: inline-block;
            width: 140px;
        }

        .info-sep {
            display: inline-block;
            margin: 0 5px;
        }

        .info-value {
            display: inline;
        }

        .requester-info {
            margin: 8px 0;
            font-size: 9px;
            border: 1px solid #999;
            padding: 6px;
            background-color: #f5f5f5;
        }

        .requester-info strong {
            display: block;
            margin-bottom: 3px;
            text-decoration: underline;
        }

        table.grid {
            width: 100%;
            margin: 8px 0;
            border-collapse: collapse;
        }

        .grid th,
        .grid td {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 9px;
            line-height: 1.2;
        }

        .grid th {
            text-align: center;
            font-weight: 700;
            background-color: #e8e8e8;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .rp-cell {
            white-space: nowrap;
        }

        .notes-section {
            margin: 8px 0;
            font-size: 9px;
        }

        .notes-section strong {
            display: block;
            margin-bottom: 3px;
            text-decoration: underline;
        }

        .notes-box {
            border: 1px solid #999;
            padding: 4px;
            min-height: 30px;
            background-color: #fafafa;
        }

        .signature-section {
            margin-top: 16px;
            font-size: 8px;
            width: 100%;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-col-left,
        .signature-col-center,
        .signature-col-right {
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .signature-col-left {
            width: 32%;
        }

        .signature-col-center {
            width: 4%;
        }

        .signature-col-right {
            width: 32%;
        }

        .signature-label {
            margin-bottom: 4px;
            font-weight: 700;
        }

        .signature-image-area {
            height: 50px;
            margin-bottom: 4px;
            border-bottom: 1px solid #000;
            position: relative;
        }

        .signature-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .signature-name {
            font-weight: 700;
            font-size: 8px;
            margin: 3px 0;
        }

        .signature-title {
            font-size: 7px;
            color: #666;
        }

        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    @php
        $docDate = $documentDate ? \Illuminate\Support\Carbon::parse($documentDate) : $generatedAt;
        $needDate = $neededDate ? \Illuminate\Support\Carbon::parse($neededDate) : $docDate;

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

        $docDateLabel = $docDate->format('d').' '.($monthMap[(int) $docDate->format('n')] ?? $docDate->format('F')).' '.$docDate->format('Y');
        $needDateLabel = $needDate->format('d').' '.($monthMap[(int) $needDate->format('n')] ?? $needDate->format('F')).' '.$needDate->format('Y');
        $generatedDateLabel = $generatedAt->format('d').' '.($monthMap[(int) $generatedAt->format('n')] ?? $generatedAt->format('F')).' '.$generatedAt->format('Y');

        $rows = collect($rows ?? []);
        $minimumRows = 12;
        $blankRows = max(0, $minimumRows - $rows->count());
    @endphp

    <div class="sheet">
        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-wrap">
                        @if($logoBgn)
                            <img src="{{ $logoBgn }}" alt="BGN" class="logo">
                        @endif
                    </td>
                    <td class="header-center">
                        <p class="header-title">Satuan Pelayanan Pemenuhan Gizi</p>
                        <p class="header-sppg">{{ strtoupper($senderName ?? '-') }}</p>
                        <p class="header-org">Yayasan Satria Merah Putih</p>
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

        <!-- Document Type -->
        <p class="doc-type">Permintaan Pembelian / Purchase Request</p>

        <!-- Document Information -->
        <div class="doc-info-row">
            <div class="info-left">
                <div>
                    <span class="info-label">Nomor PR</span>
                    <span class="info-sep">:</span>
                    <span class="info-value"><strong>{{ $documentNumber ?? '-' }}</strong></span>
                </div>
                <div>
                    <span class="info-label">Tanggal PR</span>
                    <span class="info-sep">:</span>
                    <span class="info-value">{{ $docDateLabel }}</span>
                </div>
                <div>
                    <span class="info-label">Tanggal Kebutuhan</span>
                    <span class="info-sep">:</span>
                    <span class="info-value">{{ $needDateLabel }}</span>
                </div>
            </div>
            <div class="info-right">
                <div>
                    <span class="info-label">Ref. PO</span>
                    <span class="info-sep">:</span>
                    <span class="info-value">{{ $referenceNumber ?? '-' }}</span>
                </div>
                <div>
                    <span class="info-label">Vendor Tujuan</span>
                    <span class="info-sep">:</span>
                    <span class="info-value">{{ $recipientName ?? '-' }}</span>
                </div>
                <div>
                    <span class="info-label">Penanggung Jawab</span>
                    <span class="info-sep">:</span>
                    <span class="info-value">{{ $creatorName ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Requester Information Box -->
        <div class="requester-info">
            <strong>Informasi Pemohon:</strong>
            <div>Bagian: {{ $senderName ?? '-' }}</div>
            <div>Nama PIC: {{ $creatorName ?? '-' }}</div>
            <div>Disetujui oleh: {{ $approverName ?? '-' }}</div>
        </div>

        <!-- Items Table -->
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 35%;">Nama Item / Deskripsi</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 11%;">Satuan</th>
                    <th style="width: 14%;">Harga Unit</th>
                    <th style="width: 14%;">Subtotal</th>
                    <th style="width: 12%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format((float) ($row['qty'] ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-center">{{ $row['unit'] ?? '-' }}</td>
                        <td class="text-right rp-cell">Rp {{ number_format((float) ($row['unit_price'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right rp-cell">Rp {{ number_format((float) ($row['total_price'] ?? 0), 0, ',', '.') }}</td>
                        <td style="font-size: 8px;">{{ $row['notes'] ?? '-' }}</td>
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
                        <td></td>
                    </tr>
                @endfor

                <tr style="font-weight: 700; background-color: #e8e8e8;">
                    <td colspan="5" class="text-right">TOTAL:</td>
                    <td class="text-right rp-cell">Rp {{ number_format((float) $totalAmount, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Approval Request Section -->
        <div style="margin: 8px 0; padding: 8px; background-color: #f9f9f9; border-left: 3px solid #0066cc; font-size: 9px; line-height: 1.4; color: #333;">
            <strong>Permohonan Persetujuan:</strong> Mohon di setujui pengajuan tersebut sesuai dengan permintaan kami "{{ $creatorName ?? 'User SPPG' }}" dan kami ucapkan terima kasih.
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td class="signature-col-left">
                        <div class="signature-label">Dibuat Oleh,</div>
                        <div class="signature-image-area">
                            @if(!empty($creatorSignature))
                                <img src="{{ $creatorSignature }}" alt="Tanda Tangan" class="signature-image">
                            @endif
                        </div>
                        <div class="signature-name">{{ $creatorName ?: 'Operator' }}</div>
                        <div class="signature-title">Ginan Akuntansi</div>
                    </td>
                    <td class="signature-col-center"></td>
                    <td class="signature-col-right">
                        <div class="signature-label">Disetujui Oleh,</div>
                        <div class="signature-image-area">
                            @if(!empty($approverSignature))
                                <img src="{{ $approverSignature }}" alt="Tanda Tangan" class="signature-image">
                            @endif
                        </div>
                        <div class="signature-name">{{ $approverName ?: 'PIC' }}</div>
                        <div class="signature-title">Purchasing</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Dokumen ini dicetak pada {{ $generatedDateLabel }}</div>
            <div style="margin-top: 2px; font-size: 7px; color: #666;">Sistem Manajemen Pengadaan SPPG</div>
        </div>
    </div>
</body>
</html>
