@props([
    'icon' => null,
    'logoUrl' => null,
    'size' => 'h-11 w-11',
    'radius' => 'rounded-2xl',
    'alt' => 'Logo akun',
])

@php
    $resolvedUrl = $logoUrl ?? \App\Support\BankLogos::getUrl($icon);
    $badge = \App\Support\BankLogos::find($icon);
@endphp

@if ($resolvedUrl)
    <img
        src="{{ $resolvedUrl }}"
        alt="{{ $alt }}"
        loading="lazy"
        class="{{ $size }} shrink-0 object-contain p-1 {{ $radius }} neo-inset-sm bg-surface"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
    >
    <!-- Fallback badge jika gambar PNG gagal dimuat -->
    @if ($badge)
        <span
            title="{{ $badge['name'] }}"
            class="hidden {{ $size }} shrink-0 items-center justify-center {{ $radius }} neo-inset-sm px-0.5 text-center font-bold text-white"
            style="background-color: {{ $badge['color'] }}"
        >{{ $badge['label'] }}</span>
    @else
        <span class="hidden {{ $size }} shrink-0 items-center justify-center {{ $radius }} neo-inset-sm text-lg">💼</span>
    @endif
@elseif ($badge)
    <span
        title="{{ $badge['name'] }}"
        class="{{ $size }} shrink-0 inline-flex items-center justify-center {{ $radius }} neo-inset-sm px-0.5 text-center font-bold text-white"
        style="background-color: {{ $badge['color'] }}"
    >{{ $badge['label'] }}</span>
@else
    <span class="{{ $size }} shrink-0 inline-flex items-center justify-center {{ $radius }} neo-inset-sm text-lg">💼</span>
@endif
