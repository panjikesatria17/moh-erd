@props([
    'colspan' => 1,
    'message' => 'Belum ada data.',
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-8 text-center text-slate-500">{{ $message }}</td>
</tr>
