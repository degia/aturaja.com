<x-layouts.app title="Dashboard" breadcrumb="Dashboard">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Ringkasan Keuangan</h1>
                <p class="mt-1 text-sm text-muted">
                    {{ $currentWorkspace->name }} · {{ now()->translatedFormat('F Y') }}
                </p>
            </div>
            <x-neo-button variant="primary" href="{{ route('transactions.index') }}">
                + Tambah Transaksi
            </x-neo-button>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-kpi-card label="Total Pemasukan" value="{{ \App\Support\Money::format($kpis['income']) }}" delta="Bulan ini" subtitle="▲ {{ $kpis['incomeDelta'] ?? 0 }}% dari bulan lalu" tone="positive" />
            <x-kpi-card label="Total Pengeluaran" value="{{ \App\Support\Money::format($kpis['expense']) }}" delta="Bulan ini" subtitle="{{ $kpis['expenseDelta'] === null ? 'Belum ada pengeluaran' : ($kpis['expenseDelta'] <= 0 ? '▼ '.number_format(abs($kpis['expenseDelta']), 1).'% dari bulan lalu' : '▲ '.number_format($kpis['expenseDelta'], 1).'% dari bulan lalu') }}" tone="neutral" />
            <x-kpi-card label="Net Cash Flow" value="{{ \App\Support\Money::format($kpis['net']) }}" delta="Bulan ini" subtitle="{{ $kpis['net'] >= 0 ? 'Surplus bulan ini' : 'Defisit bulan ini' }}" tone="{{ $kpis['net'] >= 0 ? 'positive' : 'negative' }}" />
            <x-kpi-card label="Net Worth" value="{{ \App\Support\Money::format($kpis['netWorth']) }}" delta="Aset − Kewajiban" subtitle="Dari saldo akun aktif" tone="info" />
        </div>

        <livewire:dashboard-charts />

        <x-neo-card class="!p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-shadow-dark/40 px-6 py-4">
                <h2 class="font-semibold text-text">Transaksi Terbaru</h2>
                <x-neo-button variant="ghost" href="{{ route('transactions.index') }}" class="!px-4 !py-2 text-sm">Lihat Semua</x-neo-button>
            </div>
            @forelse ($recentTransactions as $transaction)
                <div class="flex flex-wrap items-center gap-4 border-b border-shadow-dark/20 px-6 py-3.5 last:border-0">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl neo-inset-sm">
                        <span class="text-lg">{{ $transaction->category?->icon ?? '💸' }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-text">{{ $transaction->note ?: $transaction->type_label }}</p>
                        <p class="truncate text-xs text-muted">{{ $transaction->transaction_date->translatedFormat('d M Y') }} · {{ $transaction->account?->name }}</p>
                    </div>
                    <p class="font-bold {{ $transaction->type === 'income' ? 'text-primary' : ($transaction->type === 'transfer' ? 'text-muted' : 'text-danger') }}">
                        {{ $transaction->type === 'income' ? '+' : ($transaction->type === 'transfer' ? '⟲' : '-') }}{{ \App\Support\Money::format($transaction->amount) }}
                    </p>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl neo-inset text-muted">
                        <x-heroicon-o-paper-airplane class="h-6 w-6" />
                    </div>
                    <p class="mt-1 text-sm font-medium text-text">Belum ada transaksi</p>
                    <p class="text-sm text-muted">Catat pemasukan, pengeluaran, atau transfer pertama Anda dari halaman Transaksi.</p>
                </div>
            @endforelse
        </x-neo-card>
    </div>
</x-layouts.app>