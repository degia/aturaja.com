@props(['account' => null, 'submitLabel' => 'Simpan'])

@php
    $old = static fn (string $field, $default = null) => old($field, $account?->{$field} ?? $default);

    $initialIcon = $old('icon') ?? '';
    $initialUpload = \App\Support\BankLogos::isUploaded($initialIcon);
    $initialBadge = $initialUpload ? '' : $initialIcon;
    $initialPreview = $initialUpload ? $account?->logo_url : null;
@endphp

<div
    x-data="{
        type: @js(old('type', $account->type ?? 'cash')),
        iconValue: @js($initialBadge),
        fileUrl: @js($initialPreview),
        pick(code) {
            this.iconValue = code;
            this.fileUrl = null;
            this.$refs.logoTouched.value = '1';
        },
        onFile(event) {
            const file = event.target.files[0];
            if (! file) return;
            this.iconValue = '';
            this.fileUrl = URL.createObjectURL(file);
            this.$refs.logoTouched.value = '1';
        },
    }"
>
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

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-muted mb-2">Logo Akun (opsional)</label>

            <div class="rounded-2xl neo-inset-sm p-4">
                <p class="mb-3 text-xs text-muted">Pilih logo bank / e-wallet dari aplikasi, atau unggah logo sendiri (SVG/PNG/JPG).</p>

                <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-9">
                    @foreach (\App\Support\BankLogos::all() as $code => $bank)
                        <button
                            type="button"
                            @click="pick(@js($code))"
                            title="{{ $bank['name'] }}"
                            :class="iconValue === @js($code) ? 'ring-2 ring-primary ring-offset-2 ring-offset-surface' : ''"
                            class="flex flex-col items-center justify-center gap-1.5 rounded-xl bg-surface py-2.5 transition-all duration-200 ease-neo hover:-translate-y-0.5"
                        >
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-xl px-0.5 text-center text-[10px] font-bold leading-none text-white"
                                style="background-color: {{ $bank['color'] }}"
                            >{{ $bank['label'] }}</span>
                            <span class="max-w-full truncate text-[10px] text-muted">{{ $bank['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label
                        for="logo"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-surface px-4 py-2.5 text-sm font-medium text-text neo-inset-sm transition-all duration-200 ease-neo hover:-translate-y-0.5"
                    >
                        <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                        Unggah Logo
                        <input id="logo" type="file" name="logo" accept=".svg,.png,.jpg,.jpeg" class="hidden" @change="onFile($event)" />
                    </label>

                    <div class="flex items-center gap-3" x-show="fileUrl" x-cloak>
                        <img :src="fileUrl" alt="Pratinjau logo" class="h-10 w-10 rounded-xl object-cover neo-inset-sm" />
                        <span class="text-xs text-muted">Logo custom dipilih.</span>
                    </div>
                </div>

                <input type="hidden" name="icon" x-model="iconValue" />
                <input type="hidden" name="logo_touched" value="0" x-ref="logoTouched" />

                @error('icon')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
                @error('logo')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
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

    <div class="mt-5 flex items-center gap-3 rounded-2xl neo-inset-sm px-4 py-3">
        <input
            id="is_emergency_fund"
            type="checkbox"
            name="is_emergency_fund"
            value="1"
            class="h-4 w-4 rounded accent-primary"
            {{ $old('is_emergency_fund') ? 'checked' : '' }}
        >
        <label for="is_emergency_fund" class="text-sm text-text">
            Tandai sebagai <strong>Dana Darurat</strong>
            <span class="block text-xs text-muted">Saldo akun ini dihitung di rasio Dana Darurat (Financial Health).</span>
        </label>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-neo-button variant="primary">{{ $submitLabel }}</x-neo-button>
        <x-neo-button variant="ghost" href="{{ route('accounts.index') }}">Batal</x-neo-button>
    </div>
</div>