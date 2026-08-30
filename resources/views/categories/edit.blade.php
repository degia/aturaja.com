<x-layouts.app title="Ubah Kategori" breadcrumb="Ubah Kategori">
    <x-neo-card class="max-w-2xl">
        <h1 class="text-xl font-bold text-text mb-6">Ubah Kategori: {{ $category->name }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('categories._form', ['parents' => $parents])
            <div class="mt-6 flex items-center gap-3">
                <x-neo-button variant="primary">Perbarui Kategori</x-neo-button>
                <x-neo-button variant="ghost" href="{{ route('categories.index') }}">Batal</x-neo-button>
            </div>
        </form>
    </x-neo-card>
</x-layouts.app>