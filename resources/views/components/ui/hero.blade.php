@props([
    'eyebrow' => null,
    'title' => '',
    'description' => null,
])

<section {{ $attributes->class(['relative overflow-hidden rounded-2xl border border-slate-200 bg-linear-to-br from-slate-950 via-slate-900 to-sky-900 p-4 text-white shadow-sm sm:p-5']) }}>
    <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-cyan-300/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-16 h-56 w-56 rounded-full bg-sky-500/20 blur-3xl"></div>

    <div class="relative">
        @if($eyebrow)
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-200">{{ $eyebrow }}</p>
        @endif

        <h2 class="mt-2 text-xl font-semibold leading-tight sm:text-2xl">{{ $title }}</h2>

        @if($description)
            <p class="mt-2 max-w-3xl text-sm text-slate-200">{{ $description }}</p>
        @endif

        @if(trim($slot) !== '')
            <div class="relative mt-3">{{ $slot }}</div>
        @endif
    </div>
</section>
