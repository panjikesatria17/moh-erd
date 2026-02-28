@extends('layouts.procurement')

@section('title', 'Vendor Performance')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Vendor Performance</h2>
        <p class="text-sm text-gray-500">Analitik performa vendor berdasarkan periode evaluasi.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3 text-right">On Time</th>
                        <th class="px-4 py-3 text-right">Late</th>
                        <th class="px-4 py-3 text-right">Quality Issues</th>
                        <th class="px-4 py-3 text-right">Lead Time</th>
                        <th class="px-4 py-3 text-right">Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($performances as $performance)
                        <tr>
                            <td class="px-4 py-3">{{ $performance->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($performance->period_start)->format('d M Y') }} - {{ optional($performance->period_end)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->on_time_delivery_count }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->late_delivery_count }}</td>
                            <td class="px-4 py-3 text-right">{{ $performance->quality_issue_count }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $performance->average_lead_time_days, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $performance->score, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data performa vendor.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $performances->links() }}</div>
@endsection
