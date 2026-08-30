<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl neo-inset-sm text-muted">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/></svg>
            </div>
            <div>
                <h2 class="font-semibold text-text">Laporan Arus Kas & Pengeluaran</h2>
                <p class="text-xs text-muted">{{ $periodLabel }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="granularity" wire:change="setGranularity" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                <option value="month">Bulanan</option>
                <option value="day">Harian</option>
                <option value="year">Tahunan</option>
                <option value="custom">Kustom</option>
            </select>

            @if (! in_array($granularity, ['day', 'month'], true))
                <select wire:model.live="anchor" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                    @if ($granularity === 'year')
                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    @endif
                </select>
            @endif

            @if ($granularity === 'custom')
                <input type="date" wire:model.blur="customFrom" title="Dari tanggal" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                <span class="text-muted">–</span>
                <input type="date" wire:model.blur="customTo" title="Sampai tanggal" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
            @endif

            <div class="flex items-center gap-1">
                <button type="button" wire:click="shift(-1)" class="flex h-10 w-10 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Periode sebelumnya">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" wire:click="shift(1)" class="flex h-10 w-10 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Periode berikutnya">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl neo-inset px-4 py-3">
            <p class="text-xs font-medium text-muted">Pemasukan</p>
            <p class="mt-1 font-bold text-text">{{ \App\Support\Money::format($summary['income']) }}</p>
            <p class="mt-0.5 text-[11px] {{ $summary['incomeChange'] > 0 ? 'text-primary' : ($summary['incomeChange'] < 0 ? 'text-danger' : 'text-muted') }}">
                {{ $summary['incomeChange'] === null ? 'Periode baru' : ($summary['incomeChange'] > 0 ? '▲ '.number_format(abs($summary['incomeChange']), 1).'%' : ($summary['incomeChange'] < 0 ? '▼ '.number_format(abs($summary['incomeChange']), 1).'%' : 'Tidak berubah')) }} dibanding periode sebelumnya
            </p>
        </div>

        <div class="rounded-2xl neo-inset px-4 py-3">
            <p class="text-xs font-medium text-muted">Pengeluaran</p>
            <p class="mt-1 font-bold text-text">{{ \App\Support\Money::format($summary['expense']) }}</p>
            <p class="mt-0.5 text-[11px] {{ $summary['expenseChange'] > 0 ? 'text-danger' : ($summary['expenseChange'] < 0 ? 'text-primary' : 'text-muted') }}">
                {{ $summary['expenseChange'] === null ? 'Periode baru' : ($summary['expenseChange'] > 0 ? '▲ '.number_format(abs($summary['expenseChange']), 1).'%' : ($summary['expenseChange'] < 0 ? '▼ '.number_format(abs($summary['expenseChange']), 1).'%' : 'Tidak berubah')) }} dibanding periode sebelumnya
            </p>
        </div>

        <div class="rounded-2xl neo-inset px-4 py-3">
            <p class="text-xs font-medium text-muted">Net Cash Flow</p>
            <p class="mt-1 font-bold {{ $summary['net'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ \App\Support\Money::format($summary['net']) }}</p>
            <p class="mt-0.5 text-[11px] text-muted">{{ $summary['net'] >= 0 ? 'Surplus' : 'Defisit' }}</p>
        </div>

        <div class="rounded-2xl neo-inset px-4 py-3">
            <p class="text-xs font-medium text-muted">Rasio</p>
            <p class="mt-1 font-bold text-text">{{ number_format($summary['expense'] > 0 ? ($summary['income'] / $summary['expense']) * 100 : 0, 1) }}%</p>
            <p class="mt-0.5 text-[11px] text-muted">Daya tampung pengeluaran</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-neo-card class="xl:col-span-2">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="font-semibold text-text">Tren Arus Kas</h3>
                <div class="flex items-center gap-4 text-xs text-muted">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-primary"></span> Pemasukan</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-primary-soft"></span> Pengeluaran</span>
                </div>
            </div>
            <div
                class="relative h-72"
                wire:key="trend-{{ md5(json_encode($trendIncome).json_encode($trendExpense)) }}"
            >
                <canvas x-init="AturjaCharts.renderTrend($el, { labels: @js($trendLabels), income: @js($trendIncome), expense: @js($trendExpense) })"></canvas>
            </div>
        </x-neo-card>

        <x-neo-card>
            <div class="mb-5 flex items-center justify-between">
                <h3 class="font-semibold text-text">Pengeluaran per Kategori</h3>
                <span class="rounded-full inline-flex items-center gap-1.5 text-xs text-muted"><span class="h-2.5 w-2.5 rounded-full bg-primary-soft"></span> Periode aktif</span>
            </div>
            @if (count($breakdownValues) > 0)
                <div class="flex h-40 items-center justify-center">
                    <canvas
                        class="max-h-full max-w-full"
                        wire:key="doughnut-{{ md5(json_encode($breakdownValues)) }}"
                        x-init="AturjaCharts.renderDoughnut($el, { labels: @js($breakdownLabels), values: @js($breakdownValues), colors: @js($breakdownColors) })"
                    ></canvas>
                </div>
                <ul class="mt-5 space-y-2">
                    @foreach ($topExpenses as $item)
                        <li class="flex items-center gap-2 text-sm">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $item['color'] }}"></span>
                            <button type="button" wire:click="selectCategory({{ $item['id'] ?? 'null' }})" class="min-w-0 flex-1 truncate text-left transition-colors hover:text-primary" title="Lihat transaksi {{ $item['name'] }}">
                                {{ $item['name'] }}
                            </button>
                            <span class="font-semibold text-text">{{ \App\Support\Money::format($item['amount']) }}</span>
                            <span class="w-12 text-right text-xs text-muted">{{ number_format($item['percentage'], 1) }}%</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex h-64 items-center justify-center rounded-2xl neo-inset">
                    <p class="text-sm text-muted">Belum ada pengeluaran di periode ini.</p>
                </div>
            @endif
        </x-neo-card>
    </div>

    @if ($drilldown)
        <x-neo-card class="!p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-shadow-dark/40 px-6 py-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-text">Rincian {{ $drilldown['category']['name'] ?? 'Pengeluaran' }}</h3>
                    <span class="rounded-full neo-inset-sm px-2.5 py-0.5 text-xs font-semibold text-muted">{{ ($drilldown['category']['amount'] ?? 0) ? \App\Support\Money::format($drilldown['category']['amount']) : '' }}</span>
                </div>
                <button type="button" wire:click="selectCategory(null)" class="text-sm font-medium text-muted transition-colors hover:text-primary">Tutup rincian</button>
            </div>
            <div class="overflow-x-auto">
                @forelse ($drilldown['transactions'] as $transaction)
                    <div class="flex flex-wrap items-center gap-4 border-b border-shadow-dark/20 px-6 py-3.5 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-text">{{ $transaction->transaction_date->format('d M Y') }} · {{ $transaction->note ?: $transaction->category?->name }}</p>
                            <p class="text-xs text-muted">{{ $transaction->account?->name }}@if ($transaction->tags->isNotEmpty()) · {{ $transaction->tags->pluck('name')->join(', ') }}@endif</p>
                        </div>
                        <p class="font-semibold text-danger">-{{ \App\Support\Money::format($transaction->amount) }}</p>
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-muted">Tidak ada transaksi ditemukan.</p>
                @endforelse
            </div>
        </x-neo-card>
    @endif
</div>