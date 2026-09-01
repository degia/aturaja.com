@props([
    'icon' => null,
    'class' => null,
])

@php
    $name = \App\Support\CategoryIcons::resolve($icon) ?? 'shapes';
@endphp

<i
    data-lucide="{{ $name }}"
    class="{{ $class }}"
    style="color:#111827"
    aria-hidden="true"
></i>
