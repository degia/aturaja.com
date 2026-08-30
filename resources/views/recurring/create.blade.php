<x-layouts.app title="Tambah Aturan Berulang" breadcrumb="Tambah Aturan Berulang">
    <x-neo-card class="max-w-3xl">
        <h1 class="text-xl font-bold text-text mb-6">Tambah Aturan Berulang</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('recurring.store') }}" class="space-y-5">
            @csrf

            <div x-data="{ type: @js(old('type', 'expense')) }">
                <label for="type" class="block text-sm font-medium text-muted mb-2">Tipe Transaksi</label>
                <div class="flex gap-2">
                    @foreach (\App\Models\Transaction::TYPES as $type)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="type" value="{{ $type }}" x-model="type" class="peer sr-only">
                            <span class="flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold neo-inset-sm peer-checked:bg-primary peer-checked:text-white transition-all duration-200">
                                {{ \App\Models\Transaction::TYPE_LABELS[$type] }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('type')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror

                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="account_id" class="block text-sm font-medium text-muted mb-2">Akun</label>
                        <select id="account_id" name="account_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                            <option value="">— Pilih akun —</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->type_label }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="type === 'transfer'" x-cloak x-transition>
                        <label for="transfer_to_account_id" class="block text-sm font-medium text-muted mb-2">Akun Tujuan</label>
                        <select id="transfer_to_account_id" name="transfer_to_account_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                            <option value="">— Pilih akun tujuan —</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('transfer_to_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('transfer_to_account_id')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="type !== 'transfer'" x-cloak x-transition>
                        <label for="category_id" class="block text-sm font-medium text-muted mb-2">Kategori</label>
                        <select id="category_id" name="category_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                            <option value="">— Pilih kategori —</option>
                            @foreach ($incomeCategories as $category)
                                <option value="{{ $category->id }}" class="font-semibold text-primary" {{ old('category_id') == $category->id ? 'selected' : '' }}>⬇ {{ $category->name }}</option>
                            @endforeach
                            @foreach ($expenseCategories as $category)
                                <option value="{{ $category->id }}" class="text-danger" {{ old('category_id') == $category->id ? 'selected' : '' }}>⬆ {{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-neo-input label="Nominal" type="number" step="0.01" name="amount" value="{{ old('amount') }}" placeholder="cth. 1000000" required />
                        @error('amount')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="frequency" class="block text-sm font-medium text-muted mb-2">Frekuensi</label>
                        <select id="frequency" name="frequency" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                            @foreach (\App\Models\RecurringRule::FREQUENCIES as $frequency)
                                <option value="{{ $frequency }}" {{ old('frequency') === $frequency ? 'selected' : '' }}>{{ \App\Models\RecurringRule::FREQUENCY_LABELS[$frequency] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-neo-input label="Ulang Setiap (interval)" type="number" min="1" name="interval_count" value="{{ old('interval_count', 1) }}" />
                        @error('interval_count')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <x-neo-input label="Mulai Tanggal" type="date" name="start_date" value="{{ old('start_date', today()->format('Y-m-d')) }}" required />
                    </div>

                    <div>
                        <x-neo-input label="Berakhir (opsional)" type="date" name="end_date" value="{{ old('end_date') }}" />
                    </div>
                </div>

                <div class="mt-5">
                    <label for="note" class="block text-sm font-medium text-muted mb-2">Catatan (opsional)</label>
                    <textarea id="note" name="note" rows="3" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text placeholder:text-muted/60 outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">{{ old('note') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-neo-button variant="primary">Simpan Aturan</x-neo-button>
                <x-neo-button variant="ghost" href="{{ route('recurring.index') }}">Batal</x-neo-button>
            </div>
        </form>
    </x-neo-card>
</x-layouts.app>