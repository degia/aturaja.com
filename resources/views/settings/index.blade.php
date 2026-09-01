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

        <x-neo-card>
            <h2 class="mb-1 text-lg font-semibold text-text">Backup & Recovery</h2>
            <p class="mb-6 text-sm text-muted">Buat salinan data workspace atau pulihkan dari backup dalam format JSON.</p>

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
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-3 font-semibold text-text">Buat Backup</h3>
                        <p class="mb-4 text-sm text-muted">Pilih cakupan data yang ingin disalin, lalu unduh sebagai file JSON.</p>

                        <form method="POST" action="{{ route('backups.backup') }}" class="flex flex-col gap-4">
                            @csrf

                            <div>
                                <label for="backup-scope" class="mb-2 block text-sm font-medium text-muted">Cakupan Backup</label>
                                <select
                                    id="backup-scope"
                                    name="scope"
                                    class="w-full rounded-[14px] neo-inset-sm bg-surface px-5 py-3 text-sm text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40"
                                >
                                    <option value="full">Full data (seluruh catatan transaksi & keuangan)</option>
                                    <option value="settings">Settings saja (akun, kategori, tag, aturan berulang, budget)</option>
                                </select>
                            </div>

                            <div>
                                <x-neo-button type="submit" variant="primary">
                                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Unduh Backup
                                </x-neo-button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h3 class="mb-3 font-semibold text-text">Restore / Recovery</h3>
                        <p class="mb-4 text-sm text-muted">
                            Unggah file backup JSON untuk mengganti data workspace yang ada. Operasi ini <span class="font-semibold text-danger">menimpa seluruh data</span> sesuai cakupan backup.
                        </p>

                        <form method="POST" action="{{ route('backups.restore') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                            @csrf

                            <div>
                                <label for="backup-file" class="mb-2 block text-sm font-medium text-muted">File Backup (JSON)</label>
                                <input
                                    id="backup-file"
                                    type="file"
                                    name="backup"
                                    accept=".json,.txt,application/json"
                                    class="w-full rounded-[14px] neo-inset-sm bg-surface px-5 py-3 text-sm text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-soft file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-dark"
                                />
                                @error('backup')
                                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-neo-button type="submit" variant="danger">
                                    <x-heroicon-o-arrow-path class="h-4 w-4" /> Restore Data
                                </x-neo-button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <p class="text-sm text-muted">Hanya pemilik workspace yang dapat membuat backup dan me-restore data.</p>
            @endif
        </x-neo-card>
    </div>
</x-layouts.app>
