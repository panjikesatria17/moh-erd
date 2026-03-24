@props([
    'label' => '',
    'value' => '',
    'emphasis' => false,
])

<div {{ $attributes->class(['rounded-2xl border p-4 shadow-sm', 'border-rose-200 bg-rose-50' => $emphasis, 'border-slate-200 bg-white' => ! $emphasis]) }}>
    <p class="text-xs font-semibold uppercase tracking-wide {{ $emphasis ? 'text-rose-700' : 'text-slate-500' }}">{{ $label }}</p>
    @if(filled($value))
        <p class="mt-1 text-2xl font-semibold {{ $emphasis ? 'text-rose-700' : 'text-slate-900' }}">{{ $value }}</p>
    @endif

    @if(trim($slot) !== '')
        <div class="{{ filled($value) ? 'mt-1' : 'mt-2' }} text-sm text-slate-700">{{ $slot }}</div>
    @endif
</div>
