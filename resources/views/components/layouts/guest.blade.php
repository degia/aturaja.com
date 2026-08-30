<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · AturAja</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <livewire:styles />
</head>
<body class="min-h-full bg-bg text-text antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="{{ route('dashboard') }}" class="inline-flex h-16 w-16 items-center justify-center rounded-2xl neo-card mb-4">
                    <img src="{{ asset('logo.png') }}" alt="Logo"">
                </a>
                <h1 class="text-2xl font-bold text-text">AturAja</h1>
                <p class="mt-1 text-sm text-muted">Atur uangmu, aja.</p>
            </div>

            <x-neo-card class="p-8">
                {{ $slot }}
            </x-neo-card>
        </div>
    </div>

    <livewire:scripts />
</body>
</html>
