<x-layouts.app title="Akun & Dompet" breadcrumb="Akun & Dompet">
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
                <h1 class="text-2xl font-bold text-text">Akun & Dompet</h1>
                <p class="mt-1 text-sm text-muted">Kelola tunai, rekening bank, e-wallet, dan kartu kredit.</p>
            </div>
            <x-neo-button variant="primary" href="{{ route('accounts.create') }}">+ Tambah Akun</x-neo-button>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-kpi-card
                label="Total Saldo Aktif"
                value="{{ \App\Support\Money::format($totalBalance) }}"
                icon="💰"
            />
            <x-kpi-card
                label="Total Utang Kartu Kredit"
                value="{{ \App\Support\Money::format($totalCreditBalance) }}"
                icon="💳"
            />
            <x-kpi-card
                label="Jumlah Akun"
                value="{{ $accounts->count() }}"
                icon="🏦"
            />
        </div>

        @foreach ($accounts->groupBy('type') as $type => $group)
            <x-neo-card class="!p-0">
                <div class="flex items-center justify-between border-b border-shadow-dark/40 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-text">{{ \App\Models\Account::TYPE_LABELS[$type] }}</h2>
                        <span class="rounded-full neo-inset-sm px-2.5 py-0.5 text-xs font-semibold text-muted">{{ $group->count() }}</span>
                    </div>
                </div>

                <ul class="divide-y divide-shadow-dark/20">
                    @foreach ($group as $account)
                        <li class="flex flex-wrap items-center gap-4 px-6 py-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl neo-inset-sm text-lg">
                                {{ $account->icon ?? '💼' }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-2 font-semibold text-text">
                                    {{ $account->name }}
                                    @if ($account->is_archived)
                                        <span class="rounded-full bg-danger/10 px-2 py-0.5 text-xs font-semibold text-danger">Diarsipkan</span>
                                    @endif
                                </p>
                                @if ($account->type === 'credit_card')
                                    <p class="text-xs text-muted">
                                        Limit {{ \App\Support\Money::format($account->credit_limit) }}
                                        @if ($account->credit_limit)
                                            · Terpakai {{ round(($account->balance / $account->credit_limit) * 100) }}%
                                        @endif
                                        @if ($account->due_date)
                                            · Jatuh tempo tgl {{ $account->due_date }}
                                        @endif
                                    </p>
                                @else
                                    <p class="text-xs text-muted">{{ $account->transactions_count }} transaksi</p>
                                @endif
                            </div>

                            <p class="font-bold {{ $type === 'credit_card' ? 'text-danger' : 'text-text' }}">
                                {{ $type === 'credit_card' ? '-' : '' }}{{ \App\Support\Money::format($account->balance) }}
                            </p>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('accounts.edit', $account) }}" class="flex h-10 w-10 items-center justify-center rounded-2xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Edit akun">
                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                </a>

                                <form method="POST" action="{{ route('accounts.archive', $account) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-2xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-warning" title="{{ $account->is_archived ? 'Kembalikan akun' : 'Arsipkan akun' }}">
                                        <x-heroicon-o-folder class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-neo-card>
        @endforeach

        @if ($accounts->isEmpty())
            <x-neo-card>
                <div class="flex flex-col items-center gap-3 py-10 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl neo-inset text-muted">
                        <x-heroicon-o-wallet class="h-6 w-6" />
                    </div>
                    <p class="font-medium text-text">Belum ada akun</p>
                    <p class="text-sm text-muted">Tambahkan akun pertama untuk mulai mencatat.</p>
                </div>
            </x-neo-card>
        @endif
    </div>
</x-layouts.app>