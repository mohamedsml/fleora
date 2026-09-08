@props([
    'href' => null,
    'variante' => 'principal',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full px-7 py-3.5 text-sm font-medium tracking-wide transition duration-300';

    $variantes = [
        'principal' => 'bg-ink-900 text-ivory-50 hover:bg-blush-600 hover:shadow-lg hover:shadow-blush-500/20',
        'secondaire' => 'border border-ink-800/20 text-ink-800 hover:border-blush-400 hover:bg-blush-50 hover:text-blush-700',
        'clair' => 'bg-ivory-50 text-ink-900 hover:bg-blush-100',
    ];

    $classes = $base.' '.($variantes[$variante] ?? $variantes['principal']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        {{ $slot }}
    </button>
@endif
