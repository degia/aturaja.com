<div>
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-text/30 backdrop-blur-sm" @click="$wire.close()"></div>

            <div class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto neo-card p-6 lg:p-8">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text">{{ $editingId ? 'Ubah Transaksi' : 'Tambah Transaksi' }}</h2>
                    <button type="button" @click="$wire.close()" class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:text-text" title="Tutup">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="save" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-muted mb-2">Tipe Transaksi</label>
                        <div class="flex gap-2">
                            @foreach (\App\Models\Transaction::TYPES as $type)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" wire:model.live="type" value="{{ $type }}" class="peer sr-only">
                                    <span class="flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold neo-inset-sm peer-checked:bg-primary peer-checked:text-white transition-all duration-200">
                                        {{ \App\Models\Transaction::TYPE_LABELS[$type] }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('type')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="account_id" class="block text-sm font-medium text-muted mb-2">Akun</label>
                            <select id="account_id" wire:model="account_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                                <option value="">— Pilih akun —</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->type_label }})</option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($type === 'transfer')
                            <div>
                                <label for="transfer_to_account_id" class="block text-sm font-medium text-muted mb-2">Akun Tujuan</label>
                                <select id="transfer_to_account_id" wire:model="transfer_to_account_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                                    <option value="">— Pilih akun tujuan —</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                @error('transfer_to_account_id')
                                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-muted mb-2">Kategori</label>
                                <select id="category_id" wire:model="category_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                                    <option value="">— Pilih kategori —</option>
                                    @if ($type === 'income')
                                        @foreach ($incomeCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach ($expenseCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label for="amount" class="block text-sm font-medium text-muted mb-2">Nominal</label>
                            <input id="amount" type="number" step="0.01" min="0.01" wire:model="amount" placeholder="cth. 100000" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text placeholder:text-muted/60 outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                            @error('amount')
                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-muted mb-2">Tanggal</label>
                            <input id="transaction_date" type="date" wire:model="transaction_date" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                            @error('transaction_date')
                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-medium text-muted mb-2">Catatan (opsional)</label>
                        <input id="note" type="text" wire:model="note" placeholder="cth. Belanja mingguan" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text placeholder:text-muted/60 outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                        @error('note')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($tags->isNotEmpty())
                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Tag</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tags as $tag)
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-4 py-2 text-sm font-medium neo-inset-sm peer-checked:bg-primary-soft transition-all duration-200">
                                        <input type="checkbox" wire:model="tag_ids" value="{{ $tag->id }}" class="h-3 w-3 accent-primary">
                                        {{ $tag->name }}
                                    </label>
                                @endforeach
                            </div>
                            @error('tag_ids')
                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="flex items-center gap-3 pt-2">
                        <x-neo-button variant="primary" type="submit">{{ $editingId ? 'Perbarui' : 'Simpan' }}</x-neo-button>
                        <x-neo-button variant="ghost" type="button" @click="$wire.close()">Batal</x-neo-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>