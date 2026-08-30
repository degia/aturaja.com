<x-layouts.app title="Export & Riwayat" breadcrumb="Export & Riwayat">
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold text-text">Export & Riwayat</h1>
            <p class="mt-1 text-sm text-muted">Buat file PDF, Excel, atau CSV dari laporan keuangan Anda.</p>
        </div>

        <x-neo-card>
            <h2 class="mb-5 font-semibold text-text">Buat Export Baru</h2>
            <form method="POST" action="{{ route('exports.store') }}" class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-5">
                @csrf
                <div>
                    <label for="report" class="mb-2 block text-sm font-medium text-muted">Jenis Laporan</label>
                    <select id="report" name="report" class="w-full rounded-[14px] neo-inset-sm bg-surface px-5 py-3 text-sm text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40">
                        @foreach ($reports as $value => $label)
                            <option value="{{ $value }}" {{ old('report') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('report')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="type" class="mb-2 block text-sm font-medium text-muted">Format</label>
                    <select id="type" name="type" class="w-full rounded-[14px] neo-inset-sm bg-surface px-5 py-3 text-sm text-text outline-none transition-all duration-200 focus:ring-2 focus:ring-primary/40">
                        @foreach (['pdf' => 'PDF (siap cetak)', 'excel' => 'Excel', 'csv' => 'CSV'] as $value => $label)
                            <option value="{{ $value }}" {{ old('type', 'csv') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-neo-input label="Dari Tanggal" type="date" name="from" value="{{ old('from', now()->startOfMonth()->format('Y-m-d')) }}" />
                    @error('from')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-neo-input label="Sampai Tanggal" type="date" name="to" value="{{ old('to', now()->endOfMonth()->format('Y-m-d')) }}" />
                    @error('to')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-end">
                    <x-neo-button variant="primary" class="w-full">Export</x-neo-button>
                </div>
            </form>
        </x-neo-card>

        <x-neo-card class="!p-0">
            <div class="border-b border-shadow-dark/40 px-6 py-4">
                <h2 class="font-semibold text-text">Riwayat Export</h2>
            </div>
            @forelse ($jobs as $job)
                <div class="flex flex-wrap items-center gap-4 border-b border-shadow-dark/20 px-6 py-3.5 last:border-0">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl neo-inset-sm text-muted">
                        <x-heroicon-o-arrow-down-tray class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-text">{{ $job->report_label }} · {{ strtoupper($job->type) }}</p>
                        <p class="truncate text-xs text-muted">
                            {{ $job->period_start?->translatedFormat('d M Y') }} – {{ $job->period_end?->translatedFormat('d M Y') }} ·
                            {{ $job->created_at->diffForHumans() }} · {{ $job->user?->name }}
                        </p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $job->status === 'done' ? 'bg-primary/15 text-primary' : ($job->status === 'failed' ? 'bg-danger/15 text-danger' : 'bg-warning/15 text-warning') }}">
                        {{ ucfirst($job->status) }}
                    </span>
                    @if ($job->status === 'done' && $job->file_path)
                        <a href="{{ route('exports.download', $job) }}" class="flex items-center gap-2 rounded-xl neo-card px-4 py-2 text-sm font-medium text-primary transition-all duration-200 ease-neo hover:-translate-y-0.5">
                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Unduh
                        </a>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl neo-inset text-muted">
                        <x-heroicon-o-document-text class="h-6 w-6" />
                    </div>
                    <p class="mt-1 text-sm font-medium text-text">Belum ada export</p>
                    <p class="text-sm text-muted">Buat export pertama Anda menggunakan form di atas.</p>
                </div>
            @endforelse
        </x-neo-card>
    </div>
</x-layouts.app>
