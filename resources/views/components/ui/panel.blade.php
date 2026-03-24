@props([
    'title' => '',
    'subtitle' => null,
    'bodyClass' => 'p-4',
])

<section {{ $attributes->class(['overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @if($title || $subtitle)
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            @if($title)
                <h3 class="text-sm font-semibold text-slate-700">{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p class="text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</section>
