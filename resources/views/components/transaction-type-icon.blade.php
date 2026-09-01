@props([
    'type' => null,
    'class' => null,
])

@php
    $name = match ($type) {
        'income' => 'arrow-down-to-line',
        'transfer' => 'arrow-left-right',
        default => 'arrow-up-from-line',
    };
@endphp

<i
    data-lucide="{{ $name }}"
    class="{{ $class }}"
    style="color:#111827"
    aria-hidden="true"
></i>
