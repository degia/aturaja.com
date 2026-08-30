<x-layouts.app title="Transactions" breadcrumb="Transaksi">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Transaksi</h1>
                <p class="mt-1 text-sm text-muted">Catat pemasukan, pengeluaran, dan transfer antar akun.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Export CSV cepat --}}
                <form method="POST" action="{{ route('exports.store') }}" class="hidden md:block">
                    @csrf
                    <input type="hidden" name="report" value="transactions">
                    <input type="hidden" name="type" value="csv">
                    <input type="hidden" name="from" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    <input type="hidden" name="to" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                    <button type="submit" class="flex items-center gap-2 rounded-[14px] neo-card px-4 py-2.5 text-sm font-medium text-text transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" aria-label="Export CSV transaksi bulan ini">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        <span class="hidden xl:inline">Export CSV</span>
                    </button>
                </form>
                <x-neo-button variant="primary" @click="$dispatch('open-transaction-form')">+ Tambah Transaksi</x-neo-button>
            </div>
        </div>

        <livewire:transaction-table />
    </div>

    <livewire:transaction-form />
</x-layouts.app>
