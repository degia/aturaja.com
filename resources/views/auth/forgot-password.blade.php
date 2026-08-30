<x-layouts.guest title="Lupa Kata Sandi">
    <h2 class="text-lg font-bold text-text">Atur ulang kata sandi</h2>
    <p class="mt-1 mb-6 text-sm text-muted">Kami akan mengirimkan tautan reset ke email Anda.</p>

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-soft p-4 text-sm text-primary-dark">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-neo-input label="Email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus autocomplete="email" />
        </div>

        <x-neo-button variant="primary" class="w-full">
            Kirim tautan reset
        </x-neo-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Ingat kata sandi?
        <a href="{{ route('login') }}" class="font-semibold text-primary transition-colors hover:text-primary-dark">Masuk</a>
    </p>
</x-layouts.guest>