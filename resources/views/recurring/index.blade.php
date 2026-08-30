<x-layouts.app title="Transaksi Berulang" breadcrumb="Transaksi Berulang">
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-2xl bg-primary-soft p-4 text-sm font-medium text-primary-dark">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Transaksi Berulang</h1>
                <p class="mt-1 text-sm text-muted">Aturan untuk mencatat transaksi rutin secara otomatis (gaji, tagihan, iuran).</p>
            </div>
            <x-neo-button variant="primary" href="{{ route('recurring.create') }}">+ Tambah Aturan</x-neo-button>
        </div>

        <x-neo-card class="!p-0">
            <ul class="divide-y divide-shadow-dark/20">
                @forelse ($rules as $rule)
                    <li class="flex flex-wrap items-center gap-4 px-6 py-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl neo-inset-sm text-lg">
                            {{ $rule->type === 'income' ? '📥' : ($rule->type === 'transfer' ? '🔁' : '📤') }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-text">
                                {{ $rule->type === 'transfer'
                                    ? 'Transfer dari ' . $rule->account->name . ' ke ' . ($rule->transferToAccount?->name ?? '—')
                                    : \App\Models\Transaction::TYPE_LABELS[$rule->type] . ($rule->category ? ' · ' . $rule->category->name : '') }}
                            </p>
                            <p class="text-xs text-muted">
                                {{ \App\Support\Money::format($rule->amount) }} · {{ $rule->frequency_label }} · setiap {{ $rule->interval_count }}x · dari {{ $rule->account->name }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="font-semibold {{ $rule->is_active ? 'text-text' : 'text-muted line-through' }}">{{ \App\Support\Money::format($rule->amount) }}</p>
                            <p class="text-xs text-muted">Berikutnya: {{ $rule->next_run_date->format('d M Y') }}</p>
                        </div>

                        @if (! $rule->is_active)
                            <span class="rounded-full bg-danger/10 px-2.5 py-1 text-xs font-semibold text-danger">Nonaktif</span>
                        @endif

                        <form method="POST" action="{{ route('recurring.destroy', $rule) }}" onsubmit="return confirm('Hapus aturan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-2xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-danger" title="Hapus">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl neo-inset text-muted">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="mt-3 font-medium text-text">Belum ada aturan berulang</p>
                        <p class="text-sm text-muted">Buat aturan untuk transaksi rutin agar tercatat otomatis.</p>
                    </li>
                @endforelse
            </ul>
        </x-neo-card>
    </div>
</x-layouts.app>