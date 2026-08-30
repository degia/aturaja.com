<x-layouts.app title="Financial Health" breadcrumb="Financial Health">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Skor Kesehatan Keuangan</h1>
                <p class="mt-1 text-sm text-muted">Rasio tabungan, beban utang, dan kecukupan dana darurat bulan ini.</p>
            </div>
            <x-neo-button variant="ghost" href="{{ route('accounts.index') }}">
                Atur Akun Dana Darurat
            </x-neo-button>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-neo-card class="flex flex-col items-center justify-center text-center">
                <p class="mb-4 text-sm font-medium text-muted">Skor Komposit</p>
                <div
                    class="relative grid h-44 w-44 place-items-center rounded-full"
                    style="background: conic-gradient(#16A34A {{ $report['score'] }}%, #e5e7eb 0);"
                >
                    <div class="grid h-36 w-36 place-items-center rounded-full bg-surface text-center">
                        <div>
                            <p class="text-5xl font-bold text-text">{{ $report['score'] }}</p>
                            <p class="text-xs font-medium text-muted">dari 100</p>
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-base font-semibold text-text">{{ $report['score_label'] }}</p>
            </x-neo-card>

            <div class="flex flex-col gap-4 lg:col-span-2">
                @foreach ([
                    ['id' => 'savings_rate', 'icon' => 'heroicon-o-calculator', 'title' => 'Rasio Tabungan', 'unit' => '%', 'idealText' => 'Ideal ≥ 20%', 'desc' => 'Persentase pemasukan yang berhasil ditabung/disisihkan bulan ini.'],
                    ['id' => 'dti', 'icon' => 'heroicon-o-credit-card', 'title' => 'Rasio Utang (DTI)', 'unit' => '%', 'idealText' => 'Ideal ≤ 30%', 'desc' => 'Total cicilan utang bulanan dibanding total pemasukan.'],
                    ['id' => 'emergency_fund', 'icon' => 'heroicon-o-shield-check', 'title' => 'Dana Darurat', 'unit' => ' bln', 'idealText' => 'Ideal 3–6 bulan', 'desc' => 'Saldo akun bertanda Dana Darurat dibagi rata-rata pengeluaran bulanan.'],
                ] as $ratio)
                    @php
                        $data = $report['ratios'][$ratio['id']];
                        $badge = $data['status'] === 'healthy' ? 'bg-primary/15 text-primary' : ($data['status'] === 'warning' ? 'bg-warning/15 text-warning' : 'bg-danger/15 text-danger');
                    @endphp
                    <x-neo-card class="!p-0">
                        <div class="flex items-center gap-4 p-5">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl neo-inset-sm text-primary">
                                <x-dynamic-component :component="$ratio['icon']" class="h-6 w-6" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-text">{{ $ratio['title'] }}</h3>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $badge }}">{{ $data['status_label'] }}</span>
                                </div>
                                <p class="mt-1 text-xs text-muted">{{ $ratio['desc'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-text">{{ number_format((float) $data['value'], 1) }}<span class="text-sm font-medium text-muted">{{ $ratio['unit'] }}</span></p>
                                <p class="text-[11px] text-muted">{{ $ratio['idealText'] }}</p>
                            </div>
                        </div>
                        <div class="border-t border-shadow-dark/20 px-5 py-3">
                            <p class="text-sm text-muted">{{ $data['recommendation'] }}</p>
                        </div>
                    </x-neo-card>
                @endforeach
            </div>
        </div>

        <x-neo-card>
            <div class="mb-4">
                <h3 class="font-semibold text-text">Rincian Perhitungan</h3>
                <p class="text-xs text-muted">Data yang digunakan untuk menghitung skor bulan ini.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                <x-kpi-card label="Pemasukan Bulan Ini" value="{{ \App\Support\Money::format($report['totals']['income']) }}" delta="Pemasukan" subtitle="Tipe income bulan ini" tone="positive" />
                <x-kpi-card label="Pengeluaran Bulan Ini" value="{{ \App\Support\Money::format($report['totals']['expense']) }}" delta="Pengeluaran" subtitle="Tipe expense bulan ini" tone="neutral" />
                <x-kpi-card label="Cicilan Utang/Bulan" value="{{ \App\Support\Money::format($report['totals']['monthly_installments']) }}" delta="Utang" subtitle="Sum monthly_installment" tone="negative" />
                <x-kpi-card label="Saldo Dana Darurat" value="{{ \App\Support\Money::format($report['totals']['emergency_balance']) }}" delta="Akun bertanda" subtitle="Manual flag" tone="info" />
                <x-kpi-card label="Rata-rata Pengeluaran" value="{{ \App\Support\Money::format($report['totals']['avg_monthly_expense']) }}" delta="3 bln terakhir" subtitle="Avg pengeluaran" tone="neutral" />
            </div>
        </x-neo-card>
    </div>
</x-layouts.app>
