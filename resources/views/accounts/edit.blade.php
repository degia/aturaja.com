<x-layouts.app title="Ubah Akun" breadcrumb="Ubah Akun">
    <x-neo-card class="max-w-3xl">
        <h1 class="text-xl font-bold text-text mb-6">Ubah Akun: {{ $account->name }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('accounts.update', $account) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('accounts._form', ['submitLabel' => 'Perbarui Akun'])
        </form>
    </x-neo-card>
</x-layouts.app>