@extends('layouts.procurement')

@section('title', 'Vendor Performance')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Vendor Performance</h2>
        <p class="text-sm text-gray-500">Analitik performa vendor berbasis data pengiriman aktual.</p>
    </div>

    <form method="GET" action="{{ route('ui.vendor-performances.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-3">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-4">
            <select name="vendor_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) ($selectedVendorId ?? 0) === (int) $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <div class="flex flex-wrap items-center gap-2 sm:col-span-2 md:col-span-1">
                <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Terapkan</button>
                <a href="{{ route('ui.vendor-performances.index') }}" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </div>
    </form>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Vendor Dinilai</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $summary['total_vendors'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Total Deliveries</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $summary['total_deliveries'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Rata-rata On Time</p>
            <p class="mt-1 text-xl font-semibold text-emerald-700">{{ number_format((float) ($summary['avg_on_time_rate'] ?? 0), 2, ',', '.') }}%</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Rata-rata Score</p>
            <p class="mt-1 text-xl font-semibold text-indigo-700">{{ number_format((float) ($summary['avg_score'] ?? 0), 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs md:text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3 hidden md:table-cell">Period</th>
                        <th class="px-4 py-3 text-right">Total Delivery</th>
                        <th class="px-4 py-3 text-right">On Time</th>
                        <th class="px-4 py-3 text-right">Late</th>
                        <th class="px-4 py-3 text-right">Quality Issues</th>
                        <th class="px-4 py-3 text-right">On Time Rate</th>
                        <th class="px-4 py-3 text-right hidden sm:table-cell">Lead Time</th>
                        <th class="px-4 py-3 text-right">Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($performances as $performance)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $performance->vendor_name ?? '-' }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">{{ \Illuminate\Support\Carbon::parse($performance->period_start)->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($performance->period_end)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->total_deliveries }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->on_time_delivery_count }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->late_delivery_count }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->quality_issue_count }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $performance->on_time_rate, 2, ',', '.') }}%</td>
                            <td class="px-4 py-3 text-right hidden sm:table-cell">{{ number_format((float) $performance->average_lead_time_days, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $performance->score, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">Belum ada data performa vendor pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $performances->links() }}</div>
@endsection
