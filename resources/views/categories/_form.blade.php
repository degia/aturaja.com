@props(['category' => null, 'parents' => []])

@php
    $old = static fn (string $field, $default = null) => old($field, $category?->{$field} ?? $default);
@endphp

<div x-data="{ type: @js(old('type', $category->type ?? 'expense')) }">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <x-neo-input label="Nama Kategori" name="name" value="{{ $old('name') }}" placeholder="cth. Makanan, Gaji" required />
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-muted mb-2">Tipe Kategori</label>
            <select id="type" name="type" x-model="type" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                @foreach (\App\Models\Category::TYPES as $type)
                    <option value="{{ $type }}" {{ $old('type') === $type ? 'selected' : '' }}>{{ \App\Models\Category::TYPE_LABELS[$type] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-5">
        <label for="parent_id" class="block text-sm font-medium text-muted mb-2">Kategori Induk (opsional)</label>
        <select id="parent_id" name="parent_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
            <option value="">— Tidak ada (kategori utama) —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" {{ (int) $old('parent_id') === $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-muted">Sub-kategori otomatis mengikuti tipe kategori induk.</p>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <x-neo-input label="Ikon (opsional)" name="icon" value="{{ $old('icon') }}" placeholder="cth. 🍜 / 💰" maxlength="32" />
        </div>

        <div>
            <x-neo-input label="Warna (opsional)" name="color" value="{{ $old('color') }}" placeholder="cth. #F97316" maxlength="20" />
        </div>
    </div>
</div>