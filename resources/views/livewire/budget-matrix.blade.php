<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl neo-inset-sm text-muted">
                <x-heroicon-o-chart-pie class="h-5 w-5" />
            </div>
            <div>
                <h2 class="font-semibold text-text">Anggaran per Kategori</h2>
                <p class="text-xs text-muted">Atur limit & pantau realisasi pengeluaran bulan ini.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <input type="month" wire:model="periodMonth" wire:change="setPeriodMonth" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
            <button type="button" wire:click="copyFromPreviousMonth" class="rounded-[14px] neo-card px-3 py-2.5 text-sm font-medium text-text transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Salin limit bulan sebelumnya">Salin dari bulan lalu</button>
            <div class="flex items-center gap-1">
                <button type="button" wire:click="shift(-1)" class="flex h-10 w-10 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Bulan sebelumnya">
                    <x-heroicon-o-chevron-left class="h-4 w-4" />
                </button>
                <button type="button" wire:click="shift(1)" class="flex h-10 w-10 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Bulan berikutnya">
                    <x-heroicon-o-chevron-right class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>

    @if ($saved)
        <div class="rounded-2xl neo-inset-sm bg-primary-soft/70 px-4 py-3 text-sm font-medium text-primary-dark">Perubahan anggaran berhasil disimpan.</div>
    @endif

    @php
        $statusMeta = [
            'green'  => ['label' => 'Aman',      'bar' => 'bg-primary', 'text' => 'text-primary-dark'],
            'yellow' => ['label' => 'Waspada',   'bar' => 'bg-warning', 'text' => 'text-warning'],
            'red'    => ['label' => 'Overbudget', 'bar' => 'bg-danger',  'text' => 'text-danger'],
        ];
    @endphp

    @if ($alerts->isNotEmpty())
        <div class="rounded-2xl neo-card border border-warning/30 px-5 py-4">
            <div class="flex items-center gap-2 font-semibold text-warning">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                Peringatan Anggaran
            </div>
            <ul class="mt-2 space-y-1 text-sm text-text">
                @foreach ($alerts as $a)
                    <li class="flex items-center gap-2">
                        <span>{{ $a['icon'] }}</span>
                        <span>
                            <strong>{{ $a['category_name'] }}</strong>
                            {{ $a['usage'] >= 100 ? 'telah melebihi' : 'mendekati' }} batas anggaran:
                            {{ \App\Support\Money::format($a['actual']) }} dari {{ \App\Support\Money::format($a['limit']) }}
                            ({{ number_format($a['usage'], 1) }}%).
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-neo-card>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-text">{{ $periodLabel }}</h3>
            <div class="flex items-center gap-4 text-xs text-muted">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-primary"></span> &lt; 70%</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-warning"></span> 70–99%</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-danger"></span> ≥ 100%</span>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl neo-inset px-4 py-3">
                <p class="text-xs font-medium text-muted">Total Limit</p>
                <p class="mt-1 font-bold text-text">{{ \App\Support\Money::format($totalLimit) }}</p>
            </div>
            <div class="rounded-2xl neo-inset px-4 py-3">
                <p class="text-xs font-medium text-muted">Total Realisasi</p>
                <p class="mt-1 font-bold text-text">{{ \App\Support\Money::format($totalActual) }}</p>
            </div>
            <div class="rounded-2xl neo-inset px-4 py-3">
                <p class="text-xs font-medium text-muted">Pemakaian</p>
                <p class="mt-1 font-bold {{ $totalLimit > 0 && $totalActual / $totalLimit >= 1 ? 'text-danger' : 'text-text' }}">{{ $totalLimit > 0 ? number_format(($totalActual / $totalLimit) * 100, 1) : '0.0' }}%</p>
            </div>
        </div>

        <div class="flex flex-col gap-5">
            @foreach ($categories as $category)
                @php
                    $row = $rows->firstWhere('category_id', $category->id);
                    $status = $row ? $statusMeta[$row['status']] : $statusMeta['green'];
                    $limit = isset($this->limits[$category->id]) ? (float) $this->limits[$category->id] : 0;
                    $threshold = $this->thresholds[$category->id] ?? 80;
                @endphp

                <div class="flex flex-col gap-3 rounded-2xl neo-inset-sm p-4 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 flex-col gap-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0 flex items-center gap-2">
                                <span class="text-lg">{{ $category->icon }}</span>
                                <span class="truncate font-semibold text-text">{{ $category->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="font-semibold text-text">{{ \App\Support\Money::format($row['actual'] ?? 0, 0) }}</span>
                                <span class="text-muted">/ {{ $limit > 0 ? \App\Support\Money::format($limit, 0) : '—' }}</span>
                                @if ($row)
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $status['text'] }} bg-surface">{{ $status['label'] }} · {{ number_format($row['usage'], 1) }}%</span>
                                @endif
                            </div>
                        </div>
                        <x-progress-bar value="{{ $row['usage'] ?? 0 }}" status="{{ $status['bar'] }}" />
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:w-56">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-muted">Limit</label>
                            <input type="number" min="0" step="10000" wire:model.defer="limits.{{ $category->id }}" placeholder="0" class="w-full rounded-xl neo-inset-sm bg-surface px-3 py-2 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-muted">Alert ≥</label>
                            <input type="number" min="1" max="100" wire:model.defer="thresholds.{{ $category->id }}" class="w-full rounded-xl neo-inset-sm bg-surface px-3 py-2 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                            <span class="text-xs text-muted">%</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-neo-button variant="primary" wire:click="save">Simpan Anggaran</x-neo-button>
        </div>
    </x-neo-card>
</div>
