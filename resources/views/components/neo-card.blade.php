@props(['hover' => false])

<div {{ $attributes->merge(['class' => 'neo-card p-6'.($hover ? ' neo-card--hover' : '')]) }}>
    {{ $slot }}
</div>