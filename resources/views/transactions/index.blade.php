<x-layouts.app title="Transactions" breadcrumb="Transaksi">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Transaksi</h1>
                <p class="mt-1 text-sm text-muted">Catat pemasukan, pengeluaran, dan transfer antar akun.</p>
            </div>
            <x-neo-button variant="primary" @click="$dispatch('open-transaction-form')">+ Tambah Transaksi</x-neo-button>
        </div>

        <livewire:transaction-table />
    </div>

    <livewire:transaction-form />
</x-layouts.app>