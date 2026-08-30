@php
    $currentPath = request()->path();
    $isActive = fn (string $href) => trim($href, '/') !== '' && str_starts_with($currentPath, trim($href, '/')) && ! str_starts_with(ltrim($href, '/'), '#');

    $iconGrid = 'heroicon-o-squares-2x2';
    $iconSwap = 'heroicon-o-arrow-path';
    $iconWallet = 'heroicon-o-wallet';
    $iconPie = 'heroicon-o-chart-pie';
    $iconBar = 'heroicon-o-chart-bar';
    $iconScale = 'heroicon-o-scale';
    $iconTag = 'heroicon-o-tag';
    $iconHeart = 'heroicon-o-heart';
    $iconCog = 'heroicon-o-cog-6-tooth';
    $iconClock = 'heroicon-o-clock';
    $iconExport = 'heroicon-o-arrow-down-tray';
    $iconCard = 'heroicon-o-credit-card';
    $iconUsers = 'heroicon-o-users';

    $navGroups = [
        'Main Menu' => [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'icon' => $iconGrid],
            ['label' => 'Transactions', 'href' => route('transactions.index'), 'icon' => $iconSwap],
            ['label' => 'Accounts & Wallets', 'href' => route('accounts.index'), 'icon' => $iconWallet],
            ['label' => 'Transaksi Berulang', 'href' => route('recurring.index'), 'icon' => $iconClock],
            ['label' => 'Budgets', 'href' => route('budgets.index'), 'icon' => $iconPie],
            ['label' => 'Net Worth', 'href' => route('net-worth'), 'icon' => $iconBar],
            ['label' => 'Reports', 'href' => '#', 'icon' => $iconScale],
            ['label' => 'Debt Tracker', 'href' => route('debts.index'), 'icon' => $iconHeart],
        ],
        'Workspace' => [
            ['label' => 'Categories & Tags', 'href' => route('categories.index'), 'icon' => $iconTag],
            ['label' => 'Financial Health', 'href' => '#', 'icon' => $iconHeart],
        ],
        'Management' => [
            ['label' => 'Workspace Settings', 'href' => '#', 'icon' => $iconCog],
            ['label' => 'Export & Reports History', 'href' => '#', 'icon' => $iconExport],
            ['label' => 'Billing & Subscription', 'href' => '#', 'icon' => $iconCard],
        ],
    ];

    $currentWorkspace = auth()->user()?->currentWorkspace();
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-center gap-3 px-5 h-16 lg:h-20">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl neo-card">
            <span class="text-lg font-extrabold text-primary">A</span>
        </div>
        <div :class="collapsed ? 'lg:hidden' : ''" class="min-w-0 whitespace-nowrap transition-all duration-200">
            <p class="text-base font-bold leading-tight text-text">AturAja</p>
            <p class="text-[11px] font-medium text-muted leading-tight">Atur uangmu, aja.</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-5 mt-2">
        @foreach ($navGroups as $group => $items)
            <div>
                <p :class="collapsed ? 'lg:hidden' : ''" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-muted/70">
                    {{ $group }}
                </p>

                <div :class="collapsed ? 'lg:hidden' : ''" class="h-px mb-3 mx-3 bg-gradient-to-r from-shadow-dark to-transparent"></div>

                <ul class="space-y-1">
                    @foreach ($items as $item)
                        @php $active = $isActive($item['href']); @endphp
                        <li>
                            <a
                                href="{{ $item['href'] }}"
                                class="relative flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 ease-neo {{ $active ? 'text-primary-dark bg-primary-soft/70' : 'text-muted hover:text-text hover:bg-surface' }}"
                            >
                                <span class="absolute left-1 top-1/2 h-6 w-1.5 -translate-y-1/2 rounded-full bg-primary transition-all duration-200 {{ $active ? 'opacity-100' : 'opacity-0' }}"></span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center"><x-dynamic-component :component="$item['icon']" class="w-5 h-5" /></span>
                                <span :class="collapsed ? 'lg:hidden' : ''" class="whitespace-nowrap transition-all duration-200">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-shadow-dark/40 px-3 py-4 space-y-1">
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium text-text transition-all duration-200 hover:bg-surface">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center text-primary"><x-dynamic-component :component="$iconUsers" class="w-5 h-5" /></span>
                <span :class="collapsed ? 'lg:hidden' : ''" class="min-w-0 whitespace-nowrap transition-all duration-200">
                    <span class="block truncate font-semibold">{{ $currentWorkspace?->name ?? 'Workspace' }}</span>
                    <span class="block text-xs text-muted">Ganti workspace</span>
                </span>
                <x-heroicon-o-chevron-down :class="[collapsed ? 'lg:hidden' : '', open ? 'rotate-180' : '']" class="ml-auto h-4 w-4 text-muted transition-transform duration-200" />
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute bottom-full left-3 right-3 z-20 mb-2 rounded-2xl neo-card p-2"
            >
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-muted">Workspace Anda</p>
                @forelse (auth()->user()?->workspaces as $workspace)
                    <form method="POST" action="{{ route('workspace.switch', $workspace) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm transition-colors {{ (int) session('current_workspace_id') === $workspace->id ? 'bg-primary-soft text-primary-dark' : 'text-text hover:bg-surface' }}">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ (int) session('current_workspace_id') === $workspace->id ? 'bg-primary' : 'bg-muted/40' }}"></span>
                            <span class="truncate">{{ $workspace->name }}</span>
                        </button>
                    </form>
                @empty
                    <p class="px-3 py-2 text-sm text-muted">Tidak ada workspace.</p>
                @endforelse
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium text-muted transition-all duration-200 hover:text-danger hover:bg-surface">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center">
                    <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5" />
                </span>
                <span :class="collapsed ? 'lg:hidden' : ''" class="whitespace-nowrap transition-all duration-200">Keluar</span>
            </button>
        </form>
    </div>
</div>