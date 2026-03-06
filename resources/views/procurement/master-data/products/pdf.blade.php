<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Products</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 18px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 16px;
        }

        .meta {
            margin-bottom: 12px;
            color: #374151;
        }

        .meta p {
            margin: 1px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 5px 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row td {
            background: #fffbeb;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Master Data Produk - Inventory Value</h1>
    <div class="meta">
        <p>Scope: <strong>{{ $scope === 'all' ? 'Semua Produk' : ($scope === 'catalog' ? 'Katalog' : 'Non Katalog') }}</strong></p>
        <p>Kata Kunci: <strong>{{ $keyword !== '' ? $keyword : '-' }}</strong></p>
        <p>Generated At: <strong>{{ $generatedAt->format('d-m-Y H:i:s') }}</strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">SKU</th>
                <th style="width: 20%;">Produk</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 12%;">Vendor</th>
                <th style="width: 7%;">Unit</th>
                <th style="width: 10%;" class="text-right">Harga Acuan</th>
                <th style="width: 10%;" class="text-right">Total Inventory</th>
                <th style="width: 12%;" class="text-right">Nilai Inventory</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                @php
                    $inventoryQty = (float) ($inventoryQtyByProduct[$product->id] ?? 0);
                    $inventoryValue = (float) ($inventoryValueByProduct[$product->id] ?? 0);

                    $basePrice = $product->selling_price !== null
                        ? (float) $product->selling_price
                        : ($product->government_price_cap !== null ? (float) $product->government_price_cap : 0);
                    $varianceAmount = (float) ($product->price_variance_amount ?? 0);
                    $variancePercent = (float) ($product->price_variance_percent ?? 0);
                    $referencePrice = $basePrice + $varianceAmount + ($basePrice * $variancePercent / 100);
                @endphp
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>
                        {{ $product->name }}
                        @if($product->is_ad_hoc)
                            <div class="muted">NON KATALOG</div>
                        @endif
                    </td>
                    <td>{{ $product->category?->name ?? '-' }}</td>
                    <td>{{ $product->vendor?->name ?? '-' }}</td>
                    <td class="text-center">{{ $product->unit }}</td>
                    <td class="text-right">@rupiah($referencePrice)</td>
                    <td class="text-right">{{ number_format($inventoryQty, 2, ',', '.') }} {{ $product->unit }}</td>
                    <td class="text-right">@rupiah($inventoryValue)</td>
                    <td>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Belum ada data produk.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAL VALUE ASET (SESUAI FILTER)</td>
                <td class="text-right">@rupiah($totalAssetValue)</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
