@props([
    'href' => '#',
    'variant' => 'outline',
    'size' => 'sm',
    'fullWidth' => false,
])

@php
    $variantClasses = [
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
        'neutral' => 'bg-slate-900 text-white hover:bg-slate-700',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'soft' => 'border border-slate-300 bg-slate-100 text-slate-700 hover:bg-slate-200',
        'blue-outline' => 'border border-blue-300 bg-white text-blue-700 hover:bg-blue-50',
        'rose-outline' => 'border border-rose-300 bg-white text-rose-700 hover:bg-rose-50',
    ];

    $sizeClasses = [
        'xs' => 'px-2.5 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'icon' => 'p-1.5 text-xs',
    ];

    $baseClass = 'inline-flex items-center justify-center rounded-md font-medium transition';
    $computedClass = implode(' ', array_filter([
        $baseClass,
        $variantClasses[$variant] ?? $variantClasses['outline'],
        $sizeClasses[$size] ?? $sizeClasses['sm'],
        $fullWidth ? 'w-full' : null,
    ]));
@endphp

<a href="{{ $href }}" {{ $attributes->class([$computedClass]) }}>
    {{ $slot }}
</a>
