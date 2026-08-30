@props(['asset' => null])

@php
    $old = static fn (string $field, $default = null) => old($field, $asset?->{$field} ?? $default);
@endphp

<div x-data="{ category: @js(old('category', $asset->category ?? 'cash_bank')) }">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <x-neo-input label="Nama Aset" name="name" value="{{ $old('name') }}" placeholder="cth. Rumah, Mobil, Reksa Dana" required />
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-muted mb-2">Kategori</label>
            <select id="category" name="category" x-model="category" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                @foreach (\App\Models\Asset::CATEGORIES as $cat)
                    <option value="{{ $cat }}" {{ $old('category') === $cat ? 'selected' : '' }}>{{ \App\Models\Asset::CATEGORY_LABELS[$cat] }}</option>
                @endforeach
            </select>
            @error('category')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-neo-input label="Nilai Saat Ini" type="number" step="0.01" min="0" name="current_value" value="{{ $old('current_value') }}" placeholder="0" required />
        </div>

        <div>
            <x-neo-input label="Terakhir Diperbarui (opsional)" type="date" name="last_updated_at" value="{{ $old('last_updated_at') }}" />
        </div>
    </div>

    <div x-show="category === 'cash_bank'" x-transition x-cloak class="mt-4">
        <label for="linked_account_id" class="block text-sm font-medium text-muted mb-2">Tautkan ke Akun (opsional)</label>
        <select id="linked_account_id" name="linked_account_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
            <option value="">— Tidak ditautkan —</option>
            @foreach ($accounts ?? [] as $account)
                <option value="{{ $account->id }}" {{ (int) $old('linked_account_id') === $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-muted">Aset kas/bank yang ditautkan ke akun tidak dihitung dua kali (nilainya diambil dari saldo akun).</p>
    </div>
</div>
