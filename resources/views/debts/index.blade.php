<x-layouts.app title="Debt Tracker" breadcrumb="Utang & Piutang">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Debt Tracker</h1>
                <p class="mt-1 text-sm text-muted">Pantau utang yang harus dibayar dan piutang yang perlu ditagih.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-kpi-card label="Total Utang Belum Dibayar" value="{{ \App\Support\Money::format($totalPayable) }}" delta="Payable" subtitle="Yang harus Anda bayar" tone="negative" />
            <x-kpi-card label="Total Piutang Belum Tertagih" value="{{ \App\Support\Money::format($totalReceivable) }}" delta="Receivable" subtitle="Yang harus dibayar ke Anda" tone="positive" />
        </div>

        <x-neo-card>
            <div class="mb-5">
                <h3 class="font-semibold text-text">Tambah Utang / Piutang</h3>
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
                            <x-neo-input label="Sisa Terhutang" type="number" step="0.01" min="0" name="remaining_amount" value="{{ old('remaining_amount') }}" placeholder="0" required />
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
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
                        <div class="mb-3 flex flex-col gap-3 rounded-2xl neo-inset-sm p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0">
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
                                    <p class="text-xs text-muted">@if ($debt->due_date) Jatuh tempo {{ $debt->due_date->translatedFormat('d M Y') }} · @endif {{ $debt->direction_label }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-sm text-muted line-through decoration-1">{{ \App\Support\Money::format($debt->principal_amount) }}</p>
                                        <p class="font-bold {{ $direction === 'payable' ? 'text-danger' : 'text-primary' }}">{{ \App\Support\Money::format($debt->remaining_amount) }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('debts.destroy', $debt) }}" onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted transition-colors hover:text-danger" title="Hapus">✕</button>
                                    </form>
                                </div>
                            </div>

                            @if ($debt->status !== 'paid')
                                <details class="group">
                                    <summary class="cursor-pointer text-sm font-medium text-primary hover:text-primary-dark">Catat pembayaran</summary>
                                    <form method="POST" action="{{ route('debts.payments.store', $debt) }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        @csrf
                                        <input type="number" name="amount" min="0.01" step="0.01" placeholder="Jumlah" required class="w-full px-4 py-2.5 neo-inset-sm bg-surface text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                                        <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required class="w-full px-4 py-2.5 neo-inset-sm bg-surface text-sm text-text outline-none focus:ring-2 focus:ring-primary/40">
                                        <button type="submit" class="rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-neo-extruded transition-all duration-200 hover:-translate-y-0.5">Simpan Bayaran</button>
                                    </form>
                                </details>

                                @if ($debt->payments->isNotEmpty())
                                    <ul class="mt-2 space-y-1 text-xs text-muted">
                                        @foreach ($debt->payments->sortByDesc('paid_at')->take(3) as $payment)
                                            <li class="flex justify-between">
                                                <span>{{ $payment->paid_at->format('d M Y') }} {{ $payment->note ? '· '.$payment->note : '' }}</span>
                                                <span class="font-semibold text-text">-{{ \App\Support\Money::format($payment->amount) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-muted">Belum ada {{ strtolower($title) }}.</p>
                    @endforelse
                </x-neo-card>
            @endforeach
        </div>
    </div>
</x-layouts.app>
