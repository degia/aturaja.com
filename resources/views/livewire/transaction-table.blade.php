<div>
    <x-neo-card class="!p-0">
        <div class="flex flex-wrap items-center gap-3 border-b border-shadow-dark/40 px-6 py-4">
            <div class="relative min-w-52 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-muted">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
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
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                        </button>

                        <button
                            type="button"
                            wire:click="delete({{ $transaction->id }})"
                            wire:confirm="Hapus transaksi ini? Saldo akun akan dikembalikan."
                            class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-danger"
                            title="Hapus transaksi"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl neo-inset text-muted">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
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