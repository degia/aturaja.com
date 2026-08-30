<x-layouts.app title="Tambah Akun" breadcrumb="Tambah Akun">
    <x-neo-card class="max-w-3xl">
        <h1 class="text-xl font-bold text-text mb-6">Tambah Akun Baru</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('accounts.store') }}" class="space-y-5">
            @csrf
            @include('accounts._form', ['submitLabel' => 'Simpan Akun'])
        </form>
    </x-neo-card>
</x-layouts.app>