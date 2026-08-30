@props([
    'value' => 0,
    'status' => 'green',
])

@php
    $colors = [
        'green' => 'bg-primary',
        'yellow' => 'bg-warning',
        'red' => 'bg-danger',
    ];
    $pct = min(100, max(0, (float) $value));
@endphp

<div class="w-full h-4 neo-inset-sm rounded-full overflow-hidden">
    <div
        class="h-full rounded-full {{ $colors[$status] ?? 'bg-primary' }} transition-all duration-500 ease-neo"
        style="width: {{ $pct }}%"
    ></div>
</div>