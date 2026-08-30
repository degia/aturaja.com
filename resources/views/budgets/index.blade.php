<x-layouts.app title="Budgets" breadcrumb="Anggaran">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Anggaran</h1>
                <p class="mt-1 text-sm text-muted">Tetapkan limit pengeluaran per kategori dan pantau realisasinya.</p>
            </div>
        </div>

        <livewire:budget-matrix />
    </div>
</x-layouts.app>
