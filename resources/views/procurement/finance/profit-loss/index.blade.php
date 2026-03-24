@extends('layouts.procurement')

@section('title', 'Laporan Laba Rugi')

@section('content')
    <x-ui.hero
        class="mb-4"
        eyebrow="Finance"
        title="Laporan Laba Rugi"
        description="Ringkasan performa laba rugi berbasis delivery yang sudah terbit invoice (realisasi)."
    >
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('ui.profit-loss.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                Export Excel
            </a>
            <a href="{{ route('ui.profit-loss.export-pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700">
                Export PDF
            </a>
        </div>
    </x-ui.hero>

    <x-ui.panel class="mb-4" title="Filter Periode" subtitle="Atur rentang tanggal laporan">
        <form method="GET" action="{{ route('ui.profit-loss.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label for="start_date" class="mb-1 block text-xs font-medium text-gray-600">Tanggal Mulai</label>
                <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="end_date" class="mb-1 block text-xs font-medium text-gray-600">Tanggal Selesai</label>
                <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Terapkan Filter</button>
                <a href="{{ route('ui.profit-loss.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </x-ui.panel>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat-card label="Pendapatan Realisasi" value="@rupiah($realizedRevenue)" class="border-emerald-200 bg-emerald-50" />
        <x-ui.stat-card label="HPP (COGS)" value="@rupiah($cogs)" class="border-orange-200 bg-orange-50" />
        <x-ui.stat-card label="Laba Kotor" value="@rupiah($grossProfit)" class="border-indigo-200 bg-indigo-50 {{ $grossProfit >= 0 ? '' : 'text-red-700' }}" />
        <x-ui.stat-card label="Realisasi Pembayaran" value="@rupiah($paidExpense)" class="border-rose-200 bg-rose-50" />
        <x-ui.stat-card label="Laba Bersih" value="@rupiah($netProfit)" class="border-cyan-200 bg-cyan-50 {{ $netProfit >= 0 ? '' : 'text-red-700' }}" />
    </div>

    <x-ui.panel class="mb-4" title="Ringkasan Periode">
        <p class="text-sm text-gray-700">
            Periode: <span class="font-semibold">{{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }}</span>
            s/d
            <span class="font-semibold">{{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}</span>
        </p>
        <p class="mt-1 text-xs text-gray-500">Total invoice pada periode ini: {{ number_format((int) $totalInvoicedDocuments, 0, ',', '.') }} dokumen.</p>
        <p class="mt-1 text-xs text-gray-500">Catatan: Pendapatan realisasi dihitung dari kuantitas item pada delivery yang sudah terbit invoice × harga jual produk. Jika harga jual kosong, sistem menggunakan harga beli PO sebagai fallback.</p>
        <p class="mt-1 text-xs text-gray-500">Laba bersih pada laporan ini merepresentasikan laba operasional (Pendapatan Realisasi - HPP). Nilai pembayaran ditampilkan sebagai metrik arus kas terpisah.</p>
    </x-ui.panel>

    <x-ui.panel title="Laba Rugi per SPPG" subtitle="Rekap pendapatan dan HPP per unit" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3 text-right">Pendapatan Realisasi</th>
                        <th class="px-4 py-3 text-right">HPP (COGS)</th>
                        <th class="px-4 py-3 text-right">Laba Kotor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sppgRows as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['sppg_name'] }}</td>
                            <td class="px-4 py-3 text-right">@rupiah($row['realized_revenue'])</td>
                            <td class="px-4 py-3 text-right">@rupiah($row['cogs'])</td>
                            <td class="px-4 py-3 text-right {{ $row['gross_profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">@rupiah($row['gross_profit'])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Belum ada data delivery pada periode yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>
@endsection
