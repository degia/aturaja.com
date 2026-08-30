<x-layouts.app title="Tags" breadcrumb="Tags">
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
                <h1 class="text-2xl font-bold text-text">Tags</h1>
                <p class="mt-1 text-sm text-muted">Beri label pada transaksi agar mudah difilter.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-neo-card class="lg:col-span-1 !p-6 self-start">
                <h2 class="mb-4 font-semibold text-text">Tambah Tag</h2>
                <form method="POST" action="{{ route('tags.store') }}" class="space-y-4">
                    @csrf
                    <x-neo-input label="Nama Tag" name="name" value="{{ old('name') }}" placeholder="cth. penting, rutin, travel" required />
                    <div>
                        <x-neo-input label="Warna (opsional)" name="color" value="{{ old('color') }}" placeholder="cth. #EAB308" maxlength="20" />
                    </div>
                    <x-neo-button variant="primary">Simpan Tag</x-neo-button>
                </form>
            </x-neo-card>

            <x-neo-card class="lg:col-span-2 !p-0">
                <div class="border-b border-shadow-dark/40 px-6 py-4">
                    <h2 class="font-semibold text-text">Semua Tag ({{ $tags->count() }})</h2>
                </div>

                <ul class="divide-y divide-shadow-dark/20">
                    @forelse ($tags as $tag)
                        <li class="flex flex-wrap items-center gap-3 px-6 py-4" x-data="{ editing: false }">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl neo-inset-sm">
                                <span class="h-3.5 w-3.5 rounded-full" style="background-color: {{ $tag->color ?? '#94A3B8' }}"></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-text">{{ $tag->name }}</p>
                                <p class="text-xs text-muted">{{ $tag->transactions_count }} transaksi</p>
                            </div>

                            <form method="POST" action="{{ route('tags.destroy', $tag) }}" onsubmit="return confirm('Hapus tag ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-danger" title="Hapus">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="px-6 py-10 text-center text-sm text-muted">Belum ada tag.</li>
                    @endforelse
                </ul>
            </x-neo-card>
        </div>
    </div>
</x-layouts.app>