<div>
    @if ($accounts->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-stretch gap-4">
            @foreach ($accounts as $account)
                <x-neo-card hover class="w-full !p-5 sm:w-auto sm:min-w-52 sm:flex-1">
                    <div class="flex items-center gap-3">
                        <x-account-logo :icon="$account->icon" :logo-url="$account->logo_url" size="h-10 w-10" radius="rounded-xl" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-text">{{ $account->name }}</p>
                            <p class="text-xs text-muted">{{ $account->type_label }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm font-bold {{ $account->type === 'credit_card' ? 'text-danger' : 'text-text' }}">
                        {{ $account->type === 'credit_card' ? '-' : '' }}{{ \App\Support\Money::format($account->balance) }}
                    </p>
                </x-neo-card>
            @endforeach
        </div>
    @endif

    <x-neo-card class="!p-0">
        <div class="flex flex-wrap items-center gap-3 border-b border-shadow-dark/40 px-6 py-4">
            <div class="relative min-w-52 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-muted">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                </span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari catatan, akun, kategori, tag..." class="w-full rounded-[14px] neo-inset-sm bg-surface py-2.5 pl-11 pr-4 text-sm text-text placeholder:text-muted/60 outline-none focus:ring-2 focus:ring-primary/40">
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="accountFilter" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">Semua Akun</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach

                </select>

                <select wire:model.live="categoryFilter" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->type_label }} · {{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="tagFilter" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">Semua Tag</option>
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endforeach
                </select>

                <input type="date" wire:model.live="dateFrom" title="Dari tanggal" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                <input type="date" wire:model.live="dateTo" title="Sampai tanggal" class="rounded-[14px] neo-inset-sm bg-surface px-3 py-2.5 text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
            </div>
        </div>

        <div class="overflow-x-auto">
            @forelse ($transactions as $transaction)
                <div class="flex flex-wrap items-center gap-4 border-b border-shadow-dark/20 px-6 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl neo-inset-sm">
                        <span class="text-lg">
                            {{ $transaction->type === 'income' ? '📥' : ($transaction->type === 'transfer' ? '🔁' : '📤') }}
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-text">
                            @if ($transaction->type === 'transfer')
                                {{ $transaction->account?->name }} → {{ $transaction->transferToAccount?->name }}
                            @else
                                {{ $transaction->category?->name ?? 'Tanpa kategori' }}
                                @if ($transaction->debtPayment)
                                    <span class="ml-1 rounded-full bg-warning/10 px-2 py-0.5 text-[10px] font-bold text-warning" title="Pembayaran {{ $transaction->debtPayment->debt?->direction === 'payable' ? 'utang' : 'piutang' }}">UTANG</span>
                                @endif
                            @endif
                        </p>
                        <p class="truncate text-xs text-muted">
                            {{ $transaction->transaction_date->format('d M Y') }}
                            @if ($transaction->note)
                                · {{ $transaction->note }}
                            @endif
                        </p>
                        @if ($transaction->tags->isNotEmpty())
                            <p class="mt-1 flex flex-wrap gap-1">
                                @foreach ($transaction->tags as $tag)
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="color: {{ $tag->color ?: '#64748B' }}; background-color: {{ $tag->color ?: '#64748B' }}1a">
                                        #{{ $tag->name }}
                                    </span>
                                @endforeach
                            </p>
                        @endif
                    </div>

                    <p class="font-bold {{ $transaction->type === 'income' ? 'text-primary' : ($transaction->type === 'expense' ? 'text-danger' : 'text-text') }}">
                        {{ $transaction->type === 'expense' ? '-' : ($transaction->type === 'income' ? '+' : '') }}{{ \App\Support\Money::format($transaction->amount) }}
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="$dispatch('edit-transaction', { id: {{ $transaction->id }} })"
                            class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary"
                            title="Ubah transaksi"
                        >
                            <x-heroicon-o-pencil-square class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="delete({{ $transaction->id }})"
                            wire:confirm="Hapus transaksi ini? Saldo akun akan dikembalikan."
                            class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-danger"
                            title="Hapus transaksi"
                        >
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl neo-inset text-muted">
                        <x-heroicon-o-paper-airplane class="h-6 w-6" />
                    </div>
                    <p class="mt-1 text-sm font-medium text-text">Belum ada transaksi</p>
                    <p class="text-sm text-muted">Klik "Tambah Transaksi" untuk mulai mencatat.</p>
                </div>
            @endforelse
        </div>

        @if ($transactions->hasPages())
            <div class="border-t border-shadow-dark/40 px-6 py-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-neo-card>
</div>
