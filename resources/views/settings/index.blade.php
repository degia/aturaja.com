<x-layouts.app title="Workspace Settings" breadcrumb="Workspace Settings">
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-2xl bg-primary-soft p-4 text-sm font-medium text-primary-dark">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Workspace Settings</h1>
                <p class="mt-1 text-sm text-muted">Kelola nama, mata uang, dan anggota workspace Anda.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-neo-card class="lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-text">Informasi Workspace</h2>

                @if ($errors->any())
                    <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (auth()->id() === $workspace->owner_user_id)
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <x-neo-input label="Nama Workspace" name="name" value="{{ old('name', $workspace->name) }}" placeholder="cth. Keuangan Pribadi" required />
                                @error('name')
                                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="currency" class="block text-sm font-medium text-muted mb-2">Mata Uang</label>
                                <select
                                    id="currency"
                                    name="currency"
                                    class="w-full px-5 py-3 neo-inset-sm bg-surface text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 text-sm"
                                >
                                    @foreach (['IDR' => 'IDR — Rupiah', 'USD' => 'USD — Dolar AS', 'SGD' => 'SGD — Dolar Singapura', 'MYR' => 'MYR — Ringgit', 'EUR' => 'EUR — Euro'] as $code => $label)
                                        <option value="{{ $code }}" {{ old('currency', $workspace->currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency')
                                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6">
                            <x-neo-button variant="primary">Simpan Perubahan</x-neo-button>
                        </div>
                    </form>
                @else
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <p class="block text-sm font-medium text-muted mb-2">Nama Workspace</p>
                            <p class="rounded-2xl neo-inset-sm px-5 py-3 text-sm text-text">{{ $workspace->name }}</p>
                        </div>
                        <div>
                            <p class="block text-sm font-medium text-muted mb-2">Mata Uang</p>
                            <p class="rounded-2xl neo-inset-sm px-5 py-3 text-sm text-text uppercase">{{ $workspace->currency }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-muted">Hanya pemilik workspace yang dapat mengubah pengaturan ini.</p>
                @endif
            </x-neo-card>

            <x-neo-card class="!p-0">
                <div class="border-b border-shadow-dark/40 px-6 py-4">
                    <h2 class="font-semibold text-text">Anggota Workspace</h2>
                </div>
                <ul class="divide-y divide-shadow-dark/20">
                    @forelse ($members as $member)
                        <li class="flex items-center gap-3 px-6 py-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl neo-inset-sm text-sm font-bold text-primary">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-text">{{ $member->name }}</p>
                                <p class="truncate text-xs text-muted">{{ $member->email }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $member->pivot->role === 'owner' ? 'bg-primary/15 text-primary' : 'bg-surface-muted/60 text-muted' }}">
                                {{ $member->pivot->role }}
                            </span>
                        </li>
                    @empty
                        <li class="px-6 py-6 text-sm text-muted">Tidak ada anggota.</li>
                    @endforelse
                </ul>
            </x-neo-card>
        </div>
    </div>
</x-layouts.app>
