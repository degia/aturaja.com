<x-layouts.app title="Reports" breadcrumb="Reports">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Reports</h1>
                <p class="mt-1 text-sm text-muted">Ringkasan arus kas dan rincian pengeluaran berdasarkan bulan.</p>
            </div>

            <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
                <input
                    type="month"
                    name="month"
                    value="{{ $month }}"
                    class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40"
                />
                <x-neo-button variant="primary">Tampilkan</x-neo-button>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-kpi-card label="Pemasukan" value="{{ \App\Support\Money::format($totals['income']) }}" tone="positive" subtitle="Tipe income bulan ini" />
            <x-kpi-card label="Pengeluaran" value="{{ \App\Support\Money::format($totals['expense']) }}" tone="negative" subtitle="Tipe expense bulan ini" />
            <x-kpi-card
                label="Net Cash Flow"
                value="{{ ($totals['net'] < 0 ? '-' : '') . \App\Support\Money::format(abs($totals['net'])) }}"
                :tone="$totals['net'] >= 0 ? 'positive' : 'negative'"
                subtitle="{{ $totals['net'] >= 0 ? 'Surplus' : 'Defisit' }}"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-neo-card class="xl:col-span-2">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-semibold text-text">Tren Arus Kas Harian</h3>
                    <div class="flex items-center gap-4 text-xs text-muted">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-primary"></span> Pemasukan</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-danger/70"></span> Pengeluaran</span>
                    </div>
                </div>

                @if ($totals['income'] > 0 || $totals['expense'] > 0)
                    @php
                        $max = max($trend->max('income'), $trend->max('expense'), 1);
                    @endphp
                    <div class="flex items-end gap-1.5 overflow-x-auto pb-1">
                        @foreach ($trend as $point)
                            @php
                                $dayLabel = \Carbon\Carbon::parse($point['key'])->format('d');
                            @endphp
                            <div class="flex min-w-[14px] flex-1 flex-col items-center gap-1">
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="w-full max-w-[10px] rounded-t bg-primary/80" style="height: {{ max((float) $point['income'] / $max * 100, 2) }}px" title="Pemasukan {{ \App\Support\Money::format($point['income']) }}"></div>
                                    <div class="w-full max-w-[10px] rounded-b bg-danger/70" style="height: {{ max((float) $point['expense'] / $max * 100, 2) }}px" title="Pengeluaran {{ \App\Support\Money::format($point['expense']) }}"></div>
                                </div>
                                <span class="text-[10px] text-muted">{{ $dayLabel }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center rounded-2xl neo-inset">
                        <p class="text-sm text-muted">Belum ada transaksi bulan ini.</p>
                    </div>
                @endif
            </x-neo-card>

            <x-neo-card>
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-semibold text-text">Pengeluaran per Kategori</h3>
                </div>

                @if ($expenseByCategory->isNotEmpty())
                    <ul class="space-y-4">
                        @foreach ($expenseByCategory as $item)
                            <li>
                                <div class="mb-1 flex items-center gap-2 text-sm">
                                    <span>{{ $item['icon'] }}</span>
                                    <span class="min-w-0 flex-1 truncate font-medium text-text">{{ $item['name'] }}</span>
                                    <span class="font-semibold text-text">{{ \App\Support\Money::format($item['amount']) }}</span>
                                    <span class="w-12 text-right text-xs text-muted">{{ number_format($item['percentage'], 1) }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-surface-muted/60">
                                    <div class="h-full rounded-full" style="width: {{ $item['percentage'] }}%; background: {{ $item['color'] }}"></div>
                                </div>
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
    </div>
</x-layouts.app>
