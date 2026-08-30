@props([
    'icon' => null,
    'logoUrl' => null,
    'size' => 'h-11 w-11',
    'radius' => 'rounded-2xl',
    'alt' => 'Logo akun',
])

@php
    $badge = \App\Support\BankLogos::find($icon);
@endphp

@if ($logoUrl)
    <img
        src="{{ $logoUrl }}"
        alt="{{ $alt }}"
        loading="lazy"
        class="{{ $size }} shrink-0 object-cover {{ $radius }} neo-inset-sm bg-surface"
    >
@elseif ($badge)
    <span
        title="{{ $badge['name'] }}"
        class="{{ $size }} shrink-0 inline-flex items-center justify-center {{ $radius }} neo-inset-sm px-0.5 text-center font-bold text-white"
        style="background-color: {{ $badge['color'] }}"
    >{{ $badge['label'] }}</span>
@else
    <span class="{{ $size }} shrink-0 inline-flex items-center justify-center {{ $radius }} neo-inset-sm text-lg">💼</span>
@endif
