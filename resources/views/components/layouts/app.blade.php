<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>{{ $title ?? 'Dashboard' }} · AturAja</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <livewire:styles />
</head>
<body class="h-full bg-bg text-text antialiased">
<div
    x-data="{
        collapsed: false,
        mobileOpen: false,
        init() {
            this.collapsed = window.innerWidth < 1024;
            window.addEventListener('resize', () => {
                if (window.innerWidth < 1024) { this.collapsed = true; } else { this.mobileOpen = false; }
            });
        }
    }"
    class="min-h-full"
>
    {{-- Overlay mobile --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileOpen = false"
        class="fixed inset-0 z-40 bg-text/20 backdrop-blur-sm lg:hidden"
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 flex flex-col bg-surface shadow-[8px_0_16px_-8px_var(--shadow-dark)] transition-all duration-200 ease-neo max-lg:w-64 lg:translate-x-0"
        :class="[collapsed ? 'lg:w-24' : 'lg:w-64', mobileOpen ? 'translate-x-0' : '-translate-x-full']"
    >
        @include('layouts.partials.sidebar')
    </aside>

    {{-- Main area --}}
    <div
        class="flex min-h-screen flex-col transition-all duration-200 ease-neo"
        :class="collapsed ? 'lg:pl-24' : 'lg:pl-64'"
    >
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-16 lg:h-20 items-center gap-3 border-b border-shadow-dark/40 bg-bg/80 px-4 lg:px-8 backdrop-blur-md">
            <button
                type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl neo-card text-text transition-all duration-200 ease-neo hover:-translate-y-0.5 lg:hidden"
                @click="mobileOpen = !mobileOpen"
                aria-label="Buka menu navigasi"
            >
                <x-heroicon-o-bars-3 class="h-5 w-5" />
            </button>

            <button
                type="button"
                class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-2xl neo-card text-text transition-all duration-200 ease-neo hover:-translate-y-0.5 lg:flex"
                @click="collapsed = !collapsed"
                aria-label="Lipat atau buka sidebar"
            >
                <x-heroicon-o-bars-3 class="h-5 w-5" />
            </button>

            {{-- Breadcrumb --}}
            <nav class="hidden min-w-0 items-center gap-2 truncate text-sm md:flex" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="font-medium text-muted transition-colors hover:text-primary">Dashboard</a>
                @if (isset($breadcrumb) && ! empty($breadcrumb))
                    <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-muted/60" />
                    <span class="truncate font-semibold text-text">{{ $breadcrumb }}</span>
                @endif
            </nav>

            <div class="ml-auto flex items-center gap-3 lg:gap-5">
                {{-- Search --}}
                {{-- <form action="#" class="relative hidden sm:block w-56 xl:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-muted">
                        <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                    </span>
                    <input
                        type="search"
                        placeholder="Cari transaksi, kategori, tag..."
                        class="w-full rounded-[14px] neo-inset-sm bg-surface py-2.5 pl-11 pr-4 text-sm text-text placeholder:text-muted/60 outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40"
                    >
                </form> --}}

                {{-- Export CSV cepat --}}
                <form method="POST" action="{{ route('exports.store') }}" class="hidden sm:block">
                    @csrf
                    <input type="hidden" name="report" value="transactions">
                    <input type="hidden" name="type" value="csv">
                    <input type="hidden" name="from" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    <input type="hidden" name="to" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                    <button type="submit" aria-label="Export CSV transaksi bulan ini" class="flex items-center gap-2 rounded-2xl neo-card px-4 py-2 text-sm font-medium text-text transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        Export CSV
                    </button>
                </form>

                {{-- Notifikasi --}}
                <a href="#" class="relative flex h-10 w-10 items-center justify-center rounded-2xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-text" aria-label="Notifikasi">
                    <x-heroicon-o-bell class="h-5 w-5" />
                    @if (($notificationCount ?? 0) > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
                            {{ $notificationCount }}
                        </span>
                    @endif
                </a>

                {{-- Profil --}}
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl neo-card">
                    <span class="text-sm font-bold text-primary">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </div>
        </header>

        {{-- Main content --}}
        <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>
</div>

<livewire:scripts />
</body>
</html>
