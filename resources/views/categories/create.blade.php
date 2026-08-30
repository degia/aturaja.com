<x-layouts.app title="Tambah Kategori" breadcrumb="Tambah Kategori">
    <x-neo-card class="max-w-2xl">
        <h1 class="text-xl font-bold text-text mb-6">Tambah Kategori Baru</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('categories.store') }}" class="space-y-5">
            @csrf
            @include('categories._form', ['parents' => $parents])
            <div class="mt-6 flex items-center gap-3">
                <x-neo-button variant="primary">Simpan Kategori</x-neo-button>
                <x-neo-button variant="ghost" href="{{ route('categories.index') }}">Batal</x-neo-button>
            </div>
        </form>
    </x-neo-card>
</x-layouts.app>