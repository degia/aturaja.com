@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'label' => null,
    'error' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

@if ($label)
    <label for="{{ $inputId }}" class="block text-sm font-medium text-muted mb-2">
        {{ $label }}
    </label>
@endif

<input
    @if ($inputId) id="{{ $inputId }}" @endif
    @if ($name) name="{{ $name }}" @endif
    type="{{ $type }}"
    @if ($value !== null) value="{{ $value }}" @endif
    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    {{ $attributes->merge(['class' => 'w-full px-5 py-3 neo-inset-sm bg-surface text-text placeholder:text-muted/60 outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 focus:shadow-none text-sm']) }}
>

@if ($error)
    <p class="mt-1 text-sm text-danger">{{ $error }}</p>
@endif