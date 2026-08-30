@props(['account' => null, 'submitLabel' => 'Simpan'])

@php
    $old = static fn (string $field, $default = null) => old($field, $account?->{$field} ?? $default);
@endphp

<div x-data="{ type: @js(old('type', $account->type ?? 'cash')) }">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <x-neo-input label="Nama Akun" name="name" value="{{ $old('name') }}" placeholder="cth. Dompet, BCA, GoPay" required />
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-muted mb-2">Tipe Akun</label>
            <select
                id="type"
                name="type"
                x-model="type"
                class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm"
            >
                @foreach (\App\Models\Account::TYPES as $type)
                    <option value="{{ $type }}" {{ $old('type') === $type ? 'selected' : '' }}>{{ \App\Models\Account::TYPE_LABELS[$type] }}</option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-neo-input label="Saldo Awal" type="number" step="0.01" name="balance" value="{{ $old('balance', 0) }}" placeholder="0" />
            @error('balance')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-neo-input label="Ikon (opsional)" name="icon" value="{{ $old('icon') }}" placeholder="cth. 💼 / 🏦 / 📱" maxlength="32" />
        </div>

        <div>
            <x-neo-input label="Warna (opsional)" name="color" value="{{ $old('color') }}" placeholder="cth. #16A34A" maxlength="20" />
        </div>
    </div>

    <div x-show="type === 'credit_card'" x-transition x-cloak class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
        <div>
            <x-neo-input label="Batas Limit" type="number" step="0.01" name="credit_limit" value="{{ $old('credit_limit') }}" placeholder="0" />
        </div>

        <div>
            <label for="billing_date" class="block text-sm font-medium text-muted mb-2">Tanggal Cetak Tagihan</label>
            <select id="billing_date" name="billing_date" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                <option value="">— Pilih —</option>
                @for ($day = 1; $day <= 31; $day++)
                    <option value="{{ $day }}" {{ (int) $old('billing_date') === $day ? 'selected' : '' }}>Tanggal {{ $day }}</option>
                @endfor
            </select>
        </div>

        <div>
            <label for="due_date" class="block text-sm font-medium text-muted mb-2">Tanggal Jatuh Tempo</label>
            <select id="due_date" name="due_date" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                <option value="">— Pilih —</option>
                @for ($day = 1; $day <= 31; $day++)
                    <option value="{{ $day }}" {{ (int) $old('due_date') === $day ? 'selected' : '' }}>Tanggal {{ $day }}</option>
                @endfor
            </select>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-neo-button variant="primary">{{ $submitLabel }}</x-neo-button>
        <x-neo-button variant="ghost" href="{{ route('accounts.index') }}">Batal</x-neo-button>
    </div>
</div>