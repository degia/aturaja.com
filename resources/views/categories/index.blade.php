<x-layouts.app title="Kategori & Tag" breadcrumb="Kategori & Tag">
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <div class="rounded-2xl bg-primary-soft p-4 text-sm font-medium text-primary-dark">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl bg-danger/10 p-4 text-sm font-medium text-danger">{{ session('error') }}</div>
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
                <h1 class="text-2xl font-bold text-text">Kategori</h1>
                <p class="mt-1 text-sm text-muted">Kelola kategori pemasukan & pengeluaran, serta tag.</p>
            </div>
            <x-neo-button variant="primary" href="{{ route('categories.create') }}">+ Tambah Kategori</x-neo-button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach (['income', 'expense'] as $type)
                <x-neo-card class="!p-0">
                    <div class="flex items-center gap-2 border-b border-shadow-dark/40 px-6 py-4">
                        <h2 class="font-semibold text-text">{{ \App\Models\Category::TYPE_LABELS[$type] }}</h2>
                        <span class="rounded-full neo-inset-sm px-2.5 py-0.5 text-xs font-semibold text-muted">{{ ($categories[$type] ?? collect())->count() }}</span>
                    </div>

                    <ul class="divide-y divide-shadow-dark/20">
                        @foreach ($categories[$type] ?? [] as $category)
                            <li>
                                <div class="flex items-center gap-3 px-6 py-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl neo-inset-sm" style="color: {{ $category->color }}">{{ $category->icon }}</div>
                                    <div class="min-w-0 flex-1">
                                        <p class="flex items-center gap-2 text-sm font-semibold text-text">
                                            {{ $category->name }}
                                            @if ($category->is_default)
                                                <span class="rounded-full bg-primary-soft px-2 py-0.5 text-[10px] font-semibold text-primary-dark">Bawaan</span>
                                            @endif
                                            @if ($category->is_archived)
                                                <span class="rounded-full bg-danger/10 px-2 py-0.5 text-[10px] font-semibold text-danger">Arsip</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-muted">{{ $category->children_count }} sub-kategori</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('categories.edit', $category) }}" class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-primary" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        </a>

                                        <form method="POST" action="{{ route('categories.archive', $category) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-warning" title="{{ $category->is_archived ? 'Kembalikan' : 'Arsipkan' }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                                            </button>
                                        </form>

                                        @if ($categoryIsDeletable($category))
                                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl neo-card text-muted transition-all duration-200 ease-neo hover:-translate-y-0.5 hover:text-danger" title="Hapus">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                @if ($category->children->isNotEmpty())
                                    <ul class="ml-9 border-l border-shadow-dark/40 pb-2">
                                        @foreach ($category->children as $child)
                                            <li class="flex items-center gap-3 px-6 py-2">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg neo-inset-sm text-sm" style="color: {{ $child->color }}">{{ $child->icon }}</div>
                                                <p class="min-w-0 flex-1 truncate text-sm text-muted">{{ $child->name }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-neo-card>
            @endforeach
        </div>
    </div>
</x-layouts.app>