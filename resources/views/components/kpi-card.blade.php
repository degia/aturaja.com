@props([
    'label' => null,
    'value' => null,
    'delta' => null,
    'deltaType' => 'neutral',
    'tone' => 'neutral',
    'subtitle' => null,
    'icon' => null,
])

@php
    $deltaColor = match ($deltaType) {
        'up' => 'text-primary',
        'down' => 'text-danger',
        default => 'text-muted',
    };

    $subtitleColor = match ($tone) {
        'positive' => 'text-primary',
        'negative' => 'text-danger',
        'info' => 'text-sky-600',
        default => 'text-muted',
    };
@endphp

<x-neo-card hover class="!p-6">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-muted">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-text truncate">{{ $value }}</p>

            @if ($delta)
                <p class="mt-2 text-sm font-semibold {{ $deltaColor }}">{{ $delta }}</p>
            @endif

            @if ($subtitle)
                <p class="mt-0.5 text-xs {{ $subtitleColor }}">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($icon)
            <div class="shrink-0 w-11 h-11 rounded-2xl neo-inset-sm flex items-center justify-center text-primary">
                {{ $icon }}
            </div>
        @endif
    </div>
</x-neo-card>