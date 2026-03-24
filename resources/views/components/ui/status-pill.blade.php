@props([
    'value' => null,
    'classes' => [],
    'defaultClass' => 'bg-slate-100 text-slate-700',
    'uppercase' => false,
    'size' => 'xs',
])

@php
    $normalizedValue = strtolower((string) ($value ?? ''));
    $badgeClass = $classes[$normalizedValue] ?? $defaultClass;
    $sizeClass = match ($size) {
        'sm' => 'text-sm',
        'md' => 'text-base',
        default => 'text-xs',
    };

    $displayLabel = $uppercase
        ? strtoupper((string) ($value ?? '-'))
        : str((string) ($value ?? '-'))->replace('_', ' ')->title();
@endphp

<span {{ $attributes->class(["inline-flex items-center rounded-full px-2.5 py-1 font-medium {$sizeClass}", $badgeClass]) }}>
    {{ $displayLabel }}
</span>
