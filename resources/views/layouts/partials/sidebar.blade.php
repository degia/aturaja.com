@php
    $currentPath = request()->path();
    $isActive = fn (string $href) => trim($href, '/') !== '' && str_starts_with($currentPath, trim($href, '/')) && ! str_starts_with(ltrim($href, '/'), '#');

    $iconGrid = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>';
    $iconSwap = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>';
    $iconWallet = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>';
    $iconPie = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/></svg>';
    $iconBar = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>';
    $iconScale = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52v3.7c0 .697-.313 1.32-.855 1.755a9.75 9.75 0 01-3.156 1.79m-11.994-1.093A9.75 9.75 0 013 8.7V7.5c.99-.203 1.99-.377 3-.52m0 0v3.7c0 .697-.313 1.32-.855 1.755a9.75 9.75 0 003.156 1.79M4 7.5h16"/></svg>';
    $iconTag = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>';
    $iconHeart = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>';
    $iconCog = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    $iconClock = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconExport = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>';
    $iconCard = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>';
    $iconUsers = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>';

    $navGroups = [
        'Main Menu' => [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'icon' => $iconGrid],
            ['label' => 'Transactions', 'href' => route('transactions.index'), 'icon' => $iconSwap],
            ['label' => 'Accounts & Wallets', 'href' => route('accounts.index'), 'icon' => $iconWallet],
            ['label' => 'Transaksi Berulang', 'href' => route('recurring.index'), 'icon' => $iconClock],
            ['label' => 'Budgets', 'href' => route('budgets.index'), 'icon' => $iconPie],
            ['label' => 'Reports', 'href' => '#', 'icon' => $iconBar],
            ['label' => 'Debt Tracker', 'href' => '#', 'icon' => $iconScale],
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
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center">{!! $item['icon'] !!}</span>
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
                <span class="flex h-8 w-8 shrink-0 items-center justify-center text-primary">@php echo $iconUsers; @endphp</span>
                <span :class="collapsed ? 'lg:hidden' : ''" class="min-w-0 whitespace-nowrap transition-all duration-200">
                    <span class="block truncate font-semibold">{{ $currentWorkspace?->name ?? 'Workspace' }}</span>
                    <span class="block text-xs text-muted">Ganti workspace</span>
                </span>
                <svg :class="[collapsed ? 'lg:hidden' : '', open ? 'rotate-180' : '']" class="ml-auto h-4 w-4 text-muted transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
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
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                </span>
                <span :class="collapsed ? 'lg:hidden' : ''" class="whitespace-nowrap transition-all duration-200">Keluar</span>
            </button>
        </form>
    </div>
</div>