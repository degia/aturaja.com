@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
])

@php
    $variants = [
        'primary' => 'bg-primary text-white shadow-neo-extruded',
        'ghost' => 'bg-surface text-text shadow-neo-extruded',
        'danger' => 'bg-danger text-white shadow-neo-extruded',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 px-6 py-3 rounded-[14px] font-semibold text-sm transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:shadow-neo-hover active:translate-y-0 active:shadow-neo-inset focus:outline-none focus:ring-2 focus:ring-primary/50 '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif