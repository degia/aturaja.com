<x-layouts.app title="Debt Tracker" breadcrumb="Utang & Piutang">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Debt Tracker</h1>
                <p class="mt-1 text-sm text-muted">Pantau utang yang harus dibayar dan piutang yang perlu ditagih. Setiap pembayaran otomatis dicatat ke transaksi.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-kpi-card label="Total Utang Belum Dibayar" value="{{ \App\Support\Money::format($totalPayable) }}" delta="Payable" subtitle="Yang harus Anda bayar" tone="negative" />
            <x-kpi-card label="Total Piutang Belum Tertagih" value="{{ \App\Support\Money::format($totalReceivable) }}" delta="Receivable" subtitle="Yang harus dibayar ke Anda" tone="positive" />
        </div>

        <x-neo-card>
            <div class="mb-5">
                <h3 class="font-semibold text-text">Tambah Utang / Piutang</h3>
                <p class="mt-1 text-sm text-muted">Sisa terhutang dihitung otomatis dari pokok dikurangi total pembayaran.</p>
            </div>
            <form method="POST" action="{{ route('debts.store') }}" class="rounded-2xl neo-inset-sm p-4">
                @csrf
                <div x-data="{ direction: @js(old('direction', 'payable')) }">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label for="direction" class="block text-sm font-medium text-muted mb-2">Arah</label>
                            <select id="direction" name="direction" x-model="direction" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                                <option value="payable">Utang (saya bayar)</option>
                                <option value="receivable">Piutang (dibayar ke saya)</option>
                            </select>
                        </div>
                        <div>
                            <x-neo-input label="Nama / Lawan Transaksi" name="counterparty_name" value="{{ old('counterparty_name') }}" placeholder="cth. Budi, Bank XYZ" required />
                        </div>
                        <div>
                            <x-neo-input label="Jumlah Pokok" type="number" step="0.01" min="0" name="principal_amount" value="{{ old('principal_amount') }}" placeholder="0" required />
                        </div>
                        <div>
                            <label for="default_account_id" class="block text-sm font-medium text-muted mb-2">Akun Bayar/Terima</label>
                            <select id="default_account_id" name="default_account_id" class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm">
                                <option value="">— Pilih akun —</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('default_account_id') == $account->id)>{{ $account->name }} ({{ $account->type_label }})</option>
                                @endforeach
                            </select>
                            @error('default_account_id')
                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <x-neo-input label="Tenor (jumlah bulan)" type="number" min="1" max="120" name="installments_count" value="{{ old('installments_count') }}" placeholder="cth. 12" />
                        </div>
                        <div>
                            <x-neo-input label="Cicilan pertama jatuh tempo" type="date" name="first_due_date" value="{{ old('first_due_date') }}" />
                        </div>
                        <div>
                            <x-neo-input label="Jatuh Tempo (opsional)" type="date" name="due_date" value="{{ old('due_date') }}" />
                        </div>
                        <div>
                            <x-neo-input label="Catatan (opsional)" name="note" value="{{ old('note') }}" placeholder="Opsional" />
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end">
                        <x-neo-button variant="primary">+ Tambah</x-neo-button>
                    </div>
                </div>
            </form>
        </x-neo-card>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @php $groups = ['payable' => 'Utang', 'receivable' => 'Piutang']; @endphp
            @foreach ($groups as $direction => $title)
                <x-neo-card>
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="font-semibold text-text">{{ $title }}</h3>
                        <span class="text-xs text-muted">{{ $debts->where('direction', $direction)->count() }} item</span>
                    </div>

                    @forelse ($debts->where('direction', $direction) as $debt)
                        @php $progress = $debt->progress_percent; @endphp
                        <div class="mb-3 rounded-2xl neo-inset-sm p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-text">
                                        {{ $debt->counterparty_name }}
                                        @if ($debt->is_overdue)
                                            <span class="rounded-full bg-danger/10 px-2 py-0.5 text-xs font-semibold text-danger">Terlambat</span>
                                        @endif
                                        @if ($debt->status === 'paid')
                                            <span class="rounded-full bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary-dark">Lunas</span>
                                        @elseif ($debt->status === 'partially_paid')
                                            <span class="rounded-full bg-warning/10 px-2 py-0.5 text-xs font-semibold text-warning">Sebagian</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-xs text-muted">
                                        {{ $debt->direction_label }}
                                        @if ($debt->due_date) · Jatuh tempo {{ $debt->due_date->translatedFormat('d M Y') }} @endif
                                        @if ($debt->installments_count) · Tenor {{ $debt->installments_count }} bulan @endif
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-xs text-muted">Pokok {{ \App\Support\Money::format($debt->principal_amount) }} · Terbayar {{ \App\Support\Money::format($debt->total_paid) }}</p>
                                        <p class="font-bold {{ $direction === 'payable' ? 'text-danger' : 'text-primary' }}">Sisa {{ \App\Support\Money::format($debt->remaining_amount) }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('debts.destroy', $debt) }}" onsubmit="return confirm('Hapus data ini? Transaksi terkait juga akan dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted transition-colors hover:text-danger" title="Hapus">✕</button>
                                    </form>
                                </div>
                            </div>

                            @if ((float) $debt->principal_amount > 0)
                                <div class="mt-3">
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-shadow-dark/30">
                                        <div class="h-full rounded-full {{ $direction === 'payable' ? 'bg-primary' : 'bg-primary' }} transition-all duration-500" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <p class="mt-1 text-right text-xs font-semibold text-muted">{{ $progress }}% terbayar</p>
                                </div>
                            @endif

                            @if ($debt->installments->isNotEmpty())
                                <details class="group mt-3">
                                    <summary class="cursor-pointer text-sm font-medium text-primary hover:text-primary-dark">Jadwal cicilan ({{ $debt->installments->where('status', 'paid')->count() }}/{{ $debt->installments->count() }} lunas)</summary>
                                    <div class="mt-3 overflow-x-auto rounded-2xl neo-card">
                                        <table class="w-full text-left text-sm">
                                            <thead>
                                                <tr class="border-b border-shadow-dark/40 text-xs text-muted">
                                                    <th class="px-4 py-2">Cicilan</th>
                                                    <th class="px-4 py-2">Jatuh Tempo</th>
                                                    <th class="px-4 py-2 text-right">Nominal</th>
                                                    <th class="px-4 py-2 text-right">Terbayar</th>
                                                    <th class="px-4 py-2 text-right">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($debt->installments as $installment)
                                                    <tr class="border-b border-shadow-dark/20 last:border-0">
                                                        <td class="px-4 py-2 font-semibold text-text">#{{ $installment->installment_number }}</td>
                                                        <td class="px-4 py-2 text-muted">
                                                            {{ $installment->due_date->translatedFormat('d M Y') }}
                                                            @if ($direction === 'payable' && $installment->status !== 'paid' && $installment->due_date->isPast())
                                                                <span class="ml-1 rounded-full bg-danger/10 px-2 py-0.5 text-[10px] font-bold text-danger">Terlambat</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-right font-semibold text-text">{{ \App\Support\Money::format($installment->amount) }}</td>
                                                        <td class="px-4 py-2 text-right text-muted">{{ \App\Support\Money::format($installment->amount_paid) }}</td>
                                                        <td class="px-4 py-2 text-right">
                                                            @if ($installment->status === 'paid')
                                                                <span class="font-semibold text-primary">Lunas</span>
                                                            @elseif ($installment->status === 'partial')
                                                                <span class="font-semibold text-warning">Sebagian</span>
                                                            @else
                                                                <span class="text-muted">Belum</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            @endif

                            @if ($debt->payments->isNotEmpty())
                                <details class="group mt-3">
                                    <summary class="cursor-pointer text-sm font-medium text-primary hover:text-primary-dark">Riwayat pembayaran ({{ $debt->payments->count() }})</summary>
                                    <ul class="mt-2 space-y-1 text-xs text-muted">
                                        @foreach ($debt->payments->sortByDesc('paid_at') as $payment)
                                            <li class="flex items-center justify-between gap-2">
                                                <span class="min-w-0 truncate">
                                                    {{ $payment->paid_at->format('d M Y') }}
                                                    @if ($payment->account) · {{ $payment->account->name }} @endif
                                                    @if ($payment->note) · {{ $payment->note }} @endif
                                                </span>
                                                <span class="flex shrink-0 items-center gap-2 font-semibold text-text">
                                                    -{{ \App\Support\Money::format($payment->amount) }}
                                                    <form method="POST" action="{{ route('debts.payments.destroy', [$debt, $payment]) }}" onsubmit="return confirm('Batalkan pembayaran ini dan transaksinya?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-muted transition-colors hover:text-danger" title="Batalkan pembayaran">✕</button>
                                                    </form>
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif

                            @if ($debt->status !== 'paid')
                                <details class="group mt-3">
                                    <summary class="cursor-pointer text-sm font-medium text-primary hover:text-primary-dark">Catat pembayaran</summary>
                                    <form method="POST" action="{{ route('debts.payments.store', $debt) }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @csrf
                                        <div>
                                            <label for="account_id" class="block text-xs font-medium text-muted mb-1">Akun</label>
                                            <select id="account_id" name="account_id" required class="w-full px-4 py-2.5 neo-inset-sm bg-surface text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                                                <option value="">— Pilih akun —</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}" @selected($debt->default_account_id === $account->id)>{{ $account->name }} ({{ $account->type_label }})</option>
                                                @endforeach
                                            </select>
                                            @error('account_id')
                                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="amount" class="block text-xs font-medium text-muted mb-1">Jumlah</label>
                                            <input type="number" id="amount" name="amount" min="0.01" step="0.01" max="{{ $debt->remaining_amount }}" value="{{ old('amount') }}" placeholder="Maks. sisa {{ \App\Support\Money::format($debt->remaining_amount) }}" required class="w-full px-4 py-2.5 neo-inset-sm bg-surface text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                                            @error('amount')
                                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="paid_at" class="block text-xs font-medium text-muted mb-1">Tanggal</label>
                                            <input type="date" id="paid_at" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required class="w-full px-4 py-2.5 neo-inset-sm bg-surface text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="submit" class="w-full rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-neo-extruded transition-all duration-200 hover:-translate-y-0.5">Simpan Bayaran</button>
                                        </div>
                                    </form>
                                </details>
                            @endif

                            <details class="group mt-3">
                                <summary class="cursor-pointer text-sm font-medium text-muted hover:text-text">Ubah data</summary>
                                <form method="POST" action="{{ route('debts.update', $debt) }}" class="mt-3 rounded-2xl neo-card p-4">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <x-neo-input label="Nama / Lawan Transaksi" name="counterparty_name" value="{{ $debt->counterparty_name }}" required />
                                        </div>
                                        <div>
                                            <x-neo-input label="Jumlah Pokok" type="number" step="0.01" min="0" name="principal_amount" value="{{ $debt->principal_amount }}" required />
                                        </div>
                                        <div>
                                            <label for="edit_default_account_id" class="block text-xs font-medium text-muted mb-1">Akun Bayar/Terima</label>
                                            <select id="edit_default_account_id" name="default_account_id" class="w-full px-4 py-2.5 neo-inset-sm bg-surface text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                                                <option value="">— Pilih akun —</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}" @selected($debt->default_account_id === $account->id)>{{ $account->name }} ({{ $account->type_label }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <x-neo-input label="Tenor (jumlah bulan)" type="number" min="1" max="120" name="installments_count" value="{{ $debt->installments_count }}" placeholder="Kosongkan bila tanpa cicilan" />
                                        </div>
                                        <div>
                                            <x-neo-input label="Cicilan pertama jatuh tempo" type="date" name="first_due_date" value="{{ $debt->first_due_date?->format('Y-m-d') }}" />
                                        </div>
                                        <div>
                                            <x-neo-input label="Jatuh Tempo (opsional)" type="date" name="due_date" value="{{ $debt->due_date?->format('Y-m-d') }}" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-neo-input label="Catatan (opsional)" name="note" value="{{ $debt->note }}" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-end">
                                        <x-neo-button variant="ghost" type="submit">Perbarui</x-neo-button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-muted">Belum ada {{ strtolower($title) }}.</p>
                    @endforelse
                </x-neo-card>
            @endforeach
        </div>
    </div>
</x-layouts.app>