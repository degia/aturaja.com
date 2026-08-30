<x-layouts.app title="Net Worth" breadcrumb="Net Worth">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Net Worth</h1>
                <p class="mt-1 text-sm text-muted">Pantau total aset dan kewajiban Anda beserta trennya.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-kpi-card label="Total Aset" value="{{ \App\Support\Money::format($totals['assets']) }}" delta="Nilai aset" subtitle="Aset manual + saldo akun likuid" tone="positive" />
            <x-kpi-card label="Total Kewajiban" value="{{ \App\Support\Money::format($totals['liabilities']) }}" delta="Utang tertagih" subtitle="Liability + saldo kartu kredit" tone="negative" />
            <x-kpi-card label="Net Worth" value="{{ \App\Support\Money::format($totals['net_worth']) }}" delta="Aset − Kewajiban" subtitle="{{ $totals['net_worth'] >= 0 ? 'Positif' : 'Negatif' }}" tone="{{ $totals['net_worth'] >= 0 ? 'info' : 'negative' }}" />
        </div>

        <x-neo-card>
            <div class="mb-5 flex items-center justify-between">
                <h3 class="font-semibold text-text">Tren Net Worth</h3>
                <span class="text-xs text-muted">Berdasarkan snapshot akhir bulan</span>
            </div>
            @if (count($snapshotLabels) > 0)
                <div class="relative h-80" wire:key="networth-trend">
                    <canvas x-init="AturjaCharts.renderNetWorth($el, { labels: @js($snapshotLabels), assets: @js($snapshotAssets), liabilities: @js($snapshotLiabilities), netWorth: @js($snapshotNetWorth) })"></canvas>
                </div>
            @else
                <div class="flex h-64 items-center justify-center rounded-2xl neo-inset">
                    <p class="text-sm text-muted">Snapshot bulanan akan tampil di sini otomatis di akhir bulan.</p>
                </div>
            @endif
        </x-neo-card>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-neo-card>
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-semibold text-text">Aset</h3>
                    <span class="text-xs text-muted">{{ $assets->count() }} item</span>
                </div>

                <form method="POST" action="{{ route('assets.store') }}" class="mb-6 rounded-2xl neo-inset-sm p-4">
                    @csrf
                    @include('networth._asset_form', ['asset' => null])
                    <div class="mt-4">
                        <x-neo-button variant="primary">+ Tambah Aset</x-neo-button>
                    </div>
                </form>

                <div class="flex flex-col gap-2">
                    @forelse ($assets as $asset)
                        <div class="flex items-center justify-between rounded-2xl neo-inset-sm px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-text">{{ $asset->name }}</p>
                                <p class="text-xs text-muted">{{ $asset->category_label }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <p class="font-bold text-text">{{ \App\Support\Money::format($asset->current_value) }}</p>
                                <form method="POST" action="{{ route('assets.destroy', $asset) }}" onsubmit="return confirm('Hapus aset ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted transition-colors hover:text-danger" title="Hapus">✕</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-muted">Belum ada aset.</p>
                    @endforelse
                </div>
            </x-neo-card>

            <x-neo-card>
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-semibold text-text">Kewajiban</h3>
                    <span class="text-xs text-muted">{{ $liabilities->count() }} item</span>
                </div>

                <form method="POST" action="{{ route('liabilities.store') }}" class="mb-6 rounded-2xl neo-inset-sm p-4">
                    @csrf
                    @include('networth._liability_form', ['liability' => null])
                    <div class="mt-4">
                        <x-neo-button variant="primary">+ Tambah Kewajiban</x-neo-button>
                    </div>
                </form>

                <div class="flex flex-col gap-2">
                    @forelse ($liabilities as $liability)
                        <div class="flex items-center justify-between rounded-2xl neo-inset-sm px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-text">{{ $liability->name }}</p>
                                <p class="text-xs text-muted">{{ $liability->category_label }}@if ($liability->monthly_installment) · cicilan {{ \App\Support\Money::format($liability->monthly_installment) }}/bln @endif</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <p class="font-bold text-danger">{{ \App\Support\Money::format($liability->principal_remaining) }}</p>
                                <form method="POST" action="{{ route('liabilities.destroy', $liability) }}" onsubmit="return confirm('Hapus kewajiban ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted transition-colors hover:text-danger" title="Hapus">✕</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-muted">Belum ada kewajiban.</p>
                    @endforelse
                </div>
            </x-neo-card>
        </div>
    </div>
</x-layouts.app>
