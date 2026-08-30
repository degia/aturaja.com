<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl neo-inset-sm text-muted">
                <x-heroicon-o-heart class="h-5 w-5" />
            </div>
            <div>
                <h2 class="font-semibold text-text">Skor Kesehatan Keuangan</h2>
                <p class="text-xs text-muted">Berdasarkan rasio tabungan, utang, dan dana darurat bulan ini</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="flex flex-col items-center justify-center gap-3 rounded-3xl neo-card p-8">
            <div
                class="relative grid h-40 w-40 place-items-center rounded-full"
                style="background: conic-gradient(var(--tw-ring-color, #16A34A) {{ $report['score'] }}%, #e5e7eb 0);"
            >
                <div class="grid h-32 w-32 place-items-center rounded-full bg-surface text-center">
                    <div>
                        <p class="text-4xl font-bold text-text">{{ $report['score'] }}</p>
                        <p class="text-xs font-medium text-muted">dari 100</p>
                    </div>
                </div>
            </div>
            <p class="text-sm font-semibold text-text">{{ $report['score_label'] }}</p>
        </div>

        <div class="flex flex-col gap-3 lg:col-span-2">
            @foreach ([
                ['key' => 'savings_rate', 'icon' => 'heroicon-o-calculator', 'title' => 'Rasio Tabungan', 'unit' => '%', 'ideal' => 'Ideal ≥ 20%'],
                ['key' => 'dti', 'icon' => 'heroicon-o-credit-card', 'title' => 'Rasio Utang (DTI)', 'unit' => '%', 'ideal' => 'Ideal ≤ 30%'],
                ['key' => 'emergency_fund', 'icon' => 'heroicon-o-shield-check', 'title' => 'Dana Darurat', 'unit' => ' bln', 'ideal' => 'Ideal 3–6 bulan'],
            ] as $ratio)
                @php $data = $report['ratios'][$ratio['key']]; @endphp
                <div class="flex flex-wrap items-center gap-4 rounded-2xl neo-inset px-5 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl neo-inset-sm text-muted">
                        <x-dynamic-component :component="$ratio['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-text">{{ $ratio['title'] }}</p>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $data['status'] === 'healthy' ? 'bg-primary/15 text-primary' : ($data['status'] === 'warning' ? 'bg-warning/15 text-warning' : 'bg-danger/15 text-danger') }}">
                                {{ $data['status_label'] }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted">{{ $data['recommendation'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-text">{{ number_format((float) $data['value'], 1) }}<span class="text-sm font-medium text-muted">{{ $ratio['unit'] }}</span></p>
                        <p class="text-[11px] text-muted">{{ $ratio['ideal'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
