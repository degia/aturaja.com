@props(['liability' => null])

@php
    $old = static fn (string $field, $default = null) => old($field, $liability?->{$field} ?? $default);
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <x-neo-input label="Nama Kewajiban" name="name" value="{{ $old('name') }}" placeholder="cth. KPR, Cicilan Mobil" required />
    </div>

    <div>
        <label for="category" class="block text-sm font-medium text-muted mb-2">Kategori</label>
        <select id="category" name="category" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
            @foreach (\App\Models\Liability::CATEGORIES as $cat)
                <option value="{{ $cat }}" {{ $old('category') === $cat ? 'selected' : '' }}>{{ \App\Models\Liability::CATEGORY_LABELS[$cat] }}</option>
            @endforeach
        </select>
        @error('category')
            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-neo-input label="Sisa Pokok" type="number" step="0.01" min="0" name="principal_remaining" value="{{ $old('principal_remaining') }}" placeholder="0" required />
    </div>

    <div>
        <x-neo-input label="Cicilan Bulanan (opsional)" type="number" step="0.01" min="0" name="monthly_installment" value="{{ $old('monthly_installment') }}" placeholder="0" />
    </div>

    <div>
        <x-neo-input label="Bunga % (opsional)" type="number" step="0.01" min="0" max="100" name="interest_rate" value="{{ $old('interest_rate') }}" placeholder="cth. 5.5" />
    </div>

    <div>
        <x-neo-input label="Jatuh Tempo (opsional)" type="date" name="due_date" value="{{ $old('due_date') }}" />
    </div>
</div>
